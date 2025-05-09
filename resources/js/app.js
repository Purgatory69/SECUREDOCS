import { createClient } from '@supabase/supabase-js';

// Initialize Supabase (replace with your actual keys, loaded securely)
const supabaseUrl = 'https://fywmgiuvdbsjfchfzixc.supabase.co';
const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZ5d21naXV2ZGJzamZjaGZ6iLCJyb2xlIjoiYW5vbiIsImlhdCI6MTc0NTE2MTE3MCwiZXhwIjoyMDYwNzM3MTcwfQ.wrupHgjfdyERpLDzD5uP9ZsYNnsUOICuwOTunCGmfG4'; // Use your actual anon key from .env

// Assuming you have a way to get the user ID from Laravel and pass it to your JavaScript
// This part needs to be handled correctly in your Blade file to expose userId to JavaScript
const userId = document.getElementById('user-id').value; // Example: assuming you have a hidden input with the user ID

const supabase = createClient(supabaseUrl, supabaseKey);

// ... (existing code in app.js)

// Add the modal functionality and event listener here
const newBtn = document.getElementById('newBtn');
const uploadModal = document.getElementById('uploadModal');
const modalBackdrop = document.getElementById('modalBackdrop');
const closeModalBtn = document.getElementById('closeModalBtn');
const cancelUploadBtn = document.getElementById('cancelUploadBtn');
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileList = document.getElementById('fileList');
const uploadBtn = document.getElementById('uploadBtn');
const uploadProgress = document.getElementById('uploadProgress');
const progressBar = document.getElementById('progressBar');
const progressPercentage = document.getElementById('progressPercentage');


// Make sure these functions are defined in this scope or imported
function showUploadModal() {
    uploadModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function hideUploadModal() {
    uploadModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');

    // Reset modal state
    fileInput.value = '';
    fileList.classList.add('hidden');
    fileList.innerHTML = '<div class="text-sm font-medium">Selected Files:</div>';
    uploadBtn.disabled = true;
    uploadProgress.classList.add('hidden');
    progressBar.style.width = '0%';
    progressPercentage.textContent = '0%';
}

function handleFiles(files) {
    if (files.length === 0) return;

    fileList.classList.remove('hidden');
    fileList.innerHTML = '<div class="text-sm font-medium">Selected Files:</div>';
    uploadBtn.disabled = false;

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileSize = (file.size / (1024 * 1024)).toFixed(2); // in MB

        const fileItem = document.createElement('div');
        fileItem.className = 'flex items-center justify-between text-sm p-2 bg-bg-light rounded';
        fileItem.innerHTML = `
            <div class="flex items-center">
                <span class="mr-2">${getFileIcon(file.name)}</span>
                <span class="truncate max-w-[200px]">${file.name}</span>
            </div>
            <span class="text-text-secondary text-xs">${fileSize} MB</span>
        `;

        fileList.appendChild(fileItem);
    }
}

function getFileIcon(fileName) {
    const extension = fileName.split('.').pop().toLowerCase();

    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) return '🖼️';
    if (['pdf'].includes(extension)) return '📄';
    if (['doc', 'docx'].includes(extension)) return '📝';
    if (['xls', 'xlsx', 'csv'].includes(extension)) return '📊';
    if (['ppt', 'pptx'].includes(extension)) return '🎬';
    if (['zip', 'rar', '7z'].includes(extension)) return '📦';
    return '📄';
}


// Open modal
newBtn.addEventListener('click', showUploadModal);

// Close modal
closeModalBtn.addEventListener('click', hideUploadModal);
cancelUploadBtn.addEventListener('click', hideUploadModal);
modalBackdrop.addEventListener('click', hideUploadModal);

// Prevent modal closing when clicking inside the modal
uploadModal.querySelector('.bg-white').addEventListener('click', function(event) {
    event.stopPropagation();
});

// Handle file selection
dropZone.addEventListener('click', function() {
    fileInput.click();
});

dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('border-primary');
});

dropZone.addEventListener('dragleave', function() {
    this.classList.remove('border-primary');
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('border-primary');

    if (e.dataTransfer.files.length > 0) {
        handleFiles(e.dataTransfer.files);
    }
});

fileInput.addEventListener('change', function() {
    if (this.files.length > 0) {
        handleFiles(this.files);
    }
});


uploadBtn.addEventListener('click', async function() { // Make the function async
    const files = fileInput.files;
    if (files.length === 0) return;

    // Show progress bar
    uploadProgress.classList.remove('hidden');
    uploadBtn.disabled = true;

    const file = files[0]; // Assuming only one file for simplicity

    try {
        const { data, error } = await supabase
            .storage
            .from('files') // Replace with your bucket name if it's different
            .upload(`users/${userId}/${file.name}`, file, { // Use userId variable
                cacheControl: '3600',
                upsert: false,
                onProgress: (event) => {
                    const percent = (event.loaded / event.total) * 100;
                    progressBar.style.width = `${percent}%`;
                    progressPercentage.textContent = `${Math.round(percent)}%`;
                }
            });

        if (error) {
            console.error('Upload error:', error.message);
            alert('Upload failed: ' + error.message);
        } else {
            console.log('Upload successful:', data);
            alert('Files uploaded successfully!');
            // Here you might want to save file metadata (like data.path) to your database
        }
    } catch (error) {
        console.error('Unexpected error during upload:', error);
        alert('An unexpected error occurred during upload.');
    } finally {
        // Hide progress bar and reset modal
        hideUploadModal();
    }
});
