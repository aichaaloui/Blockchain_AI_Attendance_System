module.exports = {
  networks: {
    development: {
      host: "127.0.0.1",
      port: 7545,
      network_id: "*",
      //gas: 5000000, // Augmenter la limite de gaz
      //gasPrice: 20000000000
    },
  },

  compilers: {
    solc: {
      version: "0.8.0", // Essayez de revenir à une version plus ancienne si nécessaire
    }
  }
};
