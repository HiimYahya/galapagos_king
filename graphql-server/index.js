import express from "express";
import cors from "cors";
import bodyParser from "body-parser";
import { ApolloServer } from "@apollo/server";
import { expressMiddleware } from "@apollo/server/express4";
import { MongoClient } from "mongodb";
import neo4j from "neo4j-driver";

// Connexions aux bases

// MongoDB
const mongoClient = new MongoClient("mongodb://127.0.0.1:27017");
await mongoClient.connect();
const mongo = mongoClient.db("galapagos");

// Neo4j
const neo4jDriver = neo4j.driver(
  "bolt://localhost:7687",
  neo4j.auth.basic("neo4j", "esgiesgi")
);
const neo4jSession = neo4jDriver.session();

// Schéma GraphQL minimal

const typeDefs = `
    type Port {
        id: ID
        name: String
        lat: Float
        lon: Float
    }

    type Product {
        id: ID
        name: String
        stock: Int
    }

    type Query {
        ports: [Port]
        products: [Product]
    }
`;

// Résolveurs — relient GraphQL aux bases

const resolvers = {
    Query: {
        ports: async () => {
            const result = await neo4jSession.run(
                "MATCH (p:Port) RETURN p"
            );
            return result.records.map(r => ({
                id: r.get("p").properties.id,
                name: r.get("p").properties.name,
                lat: r.get("p").properties.coords.latitude,
                lon: r.get("p").properties.coords.longitude
            }));
        },

        products: async () => {
            return await mongo.collection("products")
                .find({})
                .toArray();
        }
    }
};

// Lancement du serveur Apollo

const app = express();
app.use(cors());

const server = new ApolloServer({
    typeDefs,
    resolvers
});

await server.start();
app.use("/graphql", expressMiddleware(server));

app.listen(4000, () => {
    console.log("🚀 Serveur GraphQL prêt sur http://localhost:4000/graphql");
});
