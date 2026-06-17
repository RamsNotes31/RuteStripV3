import 'dotenv/config';
import fs from 'node:fs';
import path from 'node:path';
import { ethers } from 'ethers';

const [routeId, versionNumber] = process.argv.slice(2);

if (!routeId || !versionNumber) {
    console.error(JSON.stringify({ error: 'Missing arguments: routeId versionNumber' }));
    process.exit(1);
}

const rpcUrl = process.env.BLOCKCHAIN_RPC_URL || 'http://127.0.0.1:8545';
const contractAddress = process.env.BLOCKCHAIN_CONTRACT_ADDRESS;
const networkName = process.env.BLOCKCHAIN_NETWORK || 'localhost';

if (!contractAddress) {
    console.error(JSON.stringify({ error: 'BLOCKCHAIN_CONTRACT_ADDRESS is required' }));
    process.exit(1);
}

const artifactPath = path.resolve('artifacts/blockchain/contracts/GpxProvenanceRegistry.sol/GpxProvenanceRegistry.json');
const artifact = JSON.parse(fs.readFileSync(artifactPath, 'utf8'));

const provider = new ethers.JsonRpcProvider(rpcUrl);
const registry = new ethers.Contract(contractAddress, artifact.abi, provider);
const [fileHash, ipfsCid, registeredBy, timestamp] = await registry.getGpxVersion(routeId, versionNumber);

console.log(JSON.stringify({
    network: networkName,
    contractAddress,
    routeId: Number(routeId),
    versionNumber: Number(versionNumber),
    fileHash,
    ipfsCid,
    registeredBy,
    timestamp: Number(timestamp),
}));
