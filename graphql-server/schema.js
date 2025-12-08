import { gql } from 'apollo-server-express';

export const typeDefs = gql`

  type Coordonnees {
    latitude: Float
    longitude: Float
  }

  type Ville {
    id: ID!
    name: String!
  }

  type Position {
    id: ID!
    name: String!
  }

  type Port {
    id: ID!
    name: String!
    ville: Ville
    position: Coordonnees
  }

  type Seaplane {
    id: ID!
    modele: String
    etat: String
    positionActuelle: Position
  }

  type Route {
    id: ID!
    from: Port
    to: Port
    distance: Float
  }

  type Query {
    ports: [Port]
    villes: [Ville]
    seaplanes: [Seaplane]
    routes: [Route]
  }
`;
