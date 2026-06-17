import 'dotenv/config';
import '@nomicfoundation/hardhat-ethers';

const config = {
    solidity: '0.8.24',
    paths: {
        sources: './blockchain/contracts',
        tests: './blockchain/test',
        cache: './storage/app/blockchain/cache',
        artifacts: './artifacts',
    },
    networks: {
        localhost: {
            url: process.env.BLOCKCHAIN_RPC_URL || 'http://127.0.0.1:8545',
        },
        sepolia: {
            url: process.env.BLOCKCHAIN_RPC_URL || '',
            accounts: process.env.BLOCKCHAIN_PRIVATE_KEY ? [process.env.BLOCKCHAIN_PRIVATE_KEY] : [],
        },
    },
};

export default config;
