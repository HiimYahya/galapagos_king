export const resolvers = {
  Query: {
    seaplanes: async (_, __, { mongo }) => {
      try {
        const seaplanes = await mongo.collection('seaplanes').find().toArray();
        return seaplanes;
      } catch (error) {
        console.error("⚠️ Erreur lors de la récupération des seaplanes :", error);
        return [];
      }
    }
  },

  Seaplane: {
    positionActuelle: async (parent, _, { mongo }) => {
      try {
        if (!parent.positionActuelleId) {
          console.warn(`⚠️ Aucun ID de position trouvé pour l'hydravion ${parent._id}`);
          return null;
        }

        const position = await mongo
          .collection('positions')
          .findOne({ _id: parent.positionActuelleId });

        if (!position) {
          console.warn(`⚠️ Position non trouvée avec l'ID ${parent.positionActuelleId}`);
          return null;
        }

        return {
          id: position._id.toString(),
          name: position.name
        };
      } catch (error) {
        console.error("⚠️ Erreur lors de la récupération de la positionActuelle :", error);
        return null;
      }
    }
  }
};
