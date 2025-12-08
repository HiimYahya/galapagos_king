import express from 'express';
import { ApolloServer } from 'apollo-server-express';
import cors from 'cors';
import { MongoClient } from 'mongodb';
import neo4j from 'neo4j-driver';

import { typeDefs } from './schema.js';
import { resolvers } from './resolvers.js';

const app = express();
const PORT = 4000;

app.use(cors());

// Connexion à MongoDB
const mongoClient = new MongoClient('mongodb://localhost:27017');
await mongoClient.connect();
const mongoDb = mongoClient.db('galapagos');
console.log('✅ Connexion MongoDB établie');

// Connexion à Neo4j
const neo4jDriver = neo4j.driver(
  'bolt://localhost:7687',
  neo4j.auth.basic('neo4j', 'esgiesgi')
);
await neo4jDriver.verifyConnectivity();
console.log('✅ Connexion Neo4j établie');

// Créer Apollo Server
const server = new ApolloServer({
  typeDefs,
  resolvers,
  context: () => ({
    mongo: mongoDb,
    neo4j: neo4jDriver
  })
});

await server.start();

// Appliquer Apollo Server
server.applyMiddleware({ app, path: '/graphql' });

// Lancer serveur
app.listen(PORT, () => {
  console.log(`🚀 Serveur GraphQL prêt : http://localhost:${PORT}/graphql`);
});
