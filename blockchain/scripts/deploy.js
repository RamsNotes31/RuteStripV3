import fs from 'node:fs';
import path from 'node:path';
import hardhat from 'hardhat';

const { ethers, network } = hardhat;

const Registry = await ethers.getContractFactory('GpxProvenanceRegistry');
const registry = await Registry.deploy();
await registry.waitForDeployment();

const address = await registry.getAddress();
const deployment = {
    network: network.name,
    contract: 'GpxProvenanceRegistry',
    address,
    deployedAt: new Date().toISOString(),
};

const outputDir = path.resolve('storage/app/blockchain');
fs.mkdirSync(outputDir, { recursive: true });
fs.writeFileSync(path.join(outputDir, 'gpx-provenance-registry.json'), JSON.stringify(deployment, null, 2));

console.log(JSON.stringify(deployment));
