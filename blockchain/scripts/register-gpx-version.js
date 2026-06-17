import 'dotenv/config';
import fs from 'node:fs';
import path from 'node:path';
import { ethers } from 'ethers';

const [routeId, versionNumber, fileHash, ipfsCid] = process.argv.slice(2);

if (!routeId || !versionNumber || !fileHash || !ipfsCid) {
    console.error(JSON.stringify({ error: 'Missing arguments: routeId versionNumber fileHash ipfsCid' }));
    process.exit(1);
}

const rpcUrl = process.env.BLOCKCHAIN_RPC_URL || 'http://127.0.0.1:8545';
const privateKey = process.env.BLOCKCHAIN_PRIVATE_KEY;
const contractAddress = process.env.BLOCKCHAIN_CONTRACT_ADDRESS;
const networkName = process.env.BLOCKCHAIN_NETWORK || 'localhost';

if (!privateKey) {
    console.error(JSON.stringify({ error: 'BLOCKCHAIN_PRIVATE_KEY is required' }));
    process.exit(1);
}

if (!contractAddress) {
    console.error(JSON.stringify({ error: 'BLOCKCHAIN_CONTRACT_ADDRESS is required' }));
    process.exit(1);
}

const artifactPath = path.resolve('artifacts/blockchain/contracts/GpxProvenanceRegistry.sol/GpxProvenanceRegistry.json');
const artifact = JSON.parse(fs.readFileSync(artifactPath, 'utf8'));

const provider = new ethers.JsonRpcProvider(rpcUrl);
const wallet = new ethers.Wallet(privateKey, provider);
const registry = new ethers.Contract(contractAddress, artifact.abi, wallet);

const tx = await registry.registerGpxVersion(routeId, versionNumber, fileHash, ipfsCid);
const receipt = await tx.wait();

console.log(JSON.stringify({
    network: networkName,
    contractAddress,
    transactionHash: receipt.hash,
    blockNumber: receipt.blockNumber,
    registeredBy: wallet.address,
}));
