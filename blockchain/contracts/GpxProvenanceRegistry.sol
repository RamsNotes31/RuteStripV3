// SPDX-License-Identifier: MIT
pragma solidity ^0.8.24;

contract GpxProvenanceRegistry {
    struct GpxVersionMetadata {
        uint256 routeId;
        uint256 versionNumber;
        string fileHash;
        string ipfsCid;
        address registeredBy;
        uint256 timestamp;
        bool exists;
    }

    mapping(bytes32 => GpxVersionMetadata) private versions;

    event GpxVersionRegistered(
        uint256 indexed routeId,
        uint256 indexed versionNumber,
        string fileHash,
        string ipfsCid,
        address indexed registeredBy,
        uint256 timestamp
    );

    function registerGpxVersion(
        uint256 routeId,
        uint256 versionNumber,
        string calldata fileHash,
        string calldata ipfsCid
    ) external {
        require(routeId > 0, "Invalid route ID");
        require(versionNumber > 0, "Invalid version number");
        require(bytes(fileHash).length > 0, "File hash required");
        require(bytes(ipfsCid).length > 0, "IPFS CID required");

        bytes32 key = keccak256(abi.encodePacked(routeId, versionNumber));
        require(!versions[key].exists, "Version already registered");

        versions[key] = GpxVersionMetadata({
            routeId: routeId,
            versionNumber: versionNumber,
            fileHash: fileHash,
            ipfsCid: ipfsCid,
            registeredBy: msg.sender,
            timestamp: block.timestamp,
            exists: true
        });

        emit GpxVersionRegistered(routeId, versionNumber, fileHash, ipfsCid, msg.sender, block.timestamp);
    }

    function getGpxVersion(
        uint256 routeId,
        uint256 versionNumber
    ) external view returns (string memory fileHash, string memory ipfsCid, address registeredBy, uint256 timestamp) {
        bytes32 key = keccak256(abi.encodePacked(routeId, versionNumber));
        require(versions[key].exists, "Version not found");

        GpxVersionMetadata memory metadata = versions[key];

        return (metadata.fileHash, metadata.ipfsCid, metadata.registeredBy, metadata.timestamp);
    }

    function isRegistered(uint256 routeId, uint256 versionNumber) external view returns (bool) {
        bytes32 key = keccak256(abi.encodePacked(routeId, versionNumber));
        return versions[key].exists;
    }
}
