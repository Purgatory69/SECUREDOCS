/**
 * Client-side file encryption service for Arweave uploads
 * Uses AES-256-GCM encryption with password-based key derivation
 */

class FileEncryptionService {
    constructor() {
        this.algorithm = 'AES-GCM';
        this.keyLength = 256;
        this.ivLength = 12; // 96 bits for GCM
        this.saltLength = 16; // 128 bits
        this.iterations = 100000; // PBKDF2 iterations
    }

    /**
     * Generate a random salt
     */
    generateSalt() {
        return crypto.getRandomValues(new Uint8Array(this.saltLength));
    }

    /**
     * Generate a random IV
     */
    generateIV() {
        return crypto.getRandomValues(new Uint8Array(this.ivLength));
    }

    /**
     * Derive encryption key from password using PBKDF2
     */
    async deriveKey(password, salt) {
        const encoder = new TextEncoder();
        const passwordBuffer = encoder.encode(password);
        
        // Import password as key material
        const keyMaterial = await crypto.subtle.importKey(
            'raw',
            passwordBuffer,
            { name: 'PBKDF2' },
            false,
            ['deriveKey']
        );

        // Derive AES key
        return await crypto.subtle.deriveKey(
            {
                name: 'PBKDF2',
                salt: salt,
                iterations: this.iterations,
                hash: 'SHA-256'
            },
            keyMaterial,
            {
                name: this.algorithm,
                length: this.keyLength
            },
            false,
            ['encrypt', 'decrypt']
        );
    }

    /**
     * Encrypt file content with password
     */
    async encryptFile(fileBuffer, password) {
        try {
            console.log('🔐 Starting file encryption...');
            
            // Generate salt and IV
            const salt = this.generateSalt();
            const iv = this.generateIV();
            
            // Derive encryption key
            const key = await this.deriveKey(password, salt);
            
            // Encrypt the file
            const encryptedBuffer = await crypto.subtle.encrypt(
                {
                    name: this.algorithm,
                    iv: iv
                },
                key,
                fileBuffer
            );

            console.log('✅ File encrypted successfully');
            
            return {
                encryptedData: new Uint8Array(encryptedBuffer),
                salt: Array.from(salt),
                iv: Array.from(iv),
                algorithm: this.algorithm,
                iterations: this.iterations
            };
            
        } catch (error) {
            console.error('❌ Encryption failed:', error);
            throw new Error(`Encryption failed: ${error.message}`);
        }
    }

    /**
     * Decrypt file content with password
     */
    async decryptFile(encryptedData, password, salt, iv) {
        try {
            console.log('🔓 Starting file decryption...');
            
            // Convert arrays back to Uint8Array
            const saltArray = new Uint8Array(salt);
            const ivArray = new Uint8Array(iv);
            
            // Derive decryption key
            const key = await this.deriveKey(password, saltArray);
            
            // Decrypt the file
            const decryptedBuffer = await crypto.subtle.decrypt(
                {
                    name: this.algorithm,
                    iv: ivArray
                },
                key,
                encryptedData
            );

            console.log('✅ File decrypted successfully');
            
            return new Uint8Array(decryptedBuffer);
            
        } catch (error) {
            console.error('❌ Decryption failed:', error);
            throw new Error('Invalid password or corrupted file');
        }
    }

    /**
     * Hash password for storage (using Web Crypto API)
     */
    async hashPassword(password, salt) {
        const encoder = new TextEncoder();
        const passwordBuffer = encoder.encode(password);
        const saltArray = new Uint8Array(salt);
        
        // Combine password and salt
        const combined = new Uint8Array(passwordBuffer.length + saltArray.length);
        combined.set(passwordBuffer);
        combined.set(saltArray, passwordBuffer.length);
        
        // Hash with SHA-256
        const hashBuffer = await crypto.subtle.digest('SHA-256', combined);
        
        // Convert to hex string
        return Array.from(new Uint8Array(hashBuffer))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    }

    /**
     * Verify password against stored hash
     */
    async verifyPassword(password, storedHash, salt) {
        const computedHash = await this.hashPassword(password, salt);
        return computedHash === storedHash;
    }

    /**
     * Read file as array buffer
     */
    async readFileAsBuffer(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = () => {
                resolve(new Uint8Array(reader.result));
            };
            
            reader.onerror = () => {
                reject(new Error('Failed to read file'));
            };
            
            reader.readAsArrayBuffer(file);
        });
    }

    /**
     * Create downloadable blob from decrypted data
     */
    createDownloadBlob(decryptedData, mimeType = 'application/octet-stream') {
        return new Blob([decryptedData], { type: mimeType });
    }

    /**
     * Generate secure random password
     */
    generateSecurePassword(length = 16) {
        const charset = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        const array = new Uint8Array(length);
        crypto.getRandomValues(array);
        
        return Array.from(array, byte => charset[byte % charset.length]).join('');
    }

    /**
     * Check if Web Crypto API is available
     */
    isSupported() {
        return typeof crypto !== 'undefined' && 
               typeof crypto.subtle !== 'undefined' &&
               typeof crypto.getRandomValues !== 'undefined';
    }
}

// Export for use in other modules
window.FileEncryptionService = FileEncryptionService;

export default FileEncryptionService;
