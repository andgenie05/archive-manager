/* Archive Manager - Main JavaScript */

class ArchiveManager {
    constructor() {
        this.currentDirectoryId = null;
        this.currentPath = [];
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadRootDirectory();
        this.setupAJAX();
    }

    setupEventListeners() {
        // Sidebar navigation
        document.querySelectorAll('.sidebar-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const dirId = item.dataset.dirId;
                if (dirId) {
                    this.navigateToDirectory(parseInt(dirId));
                }
            });
        });

        // Create directory button
        const createDirBtn = document.getElementById('createDirBtn');
        if (createDirBtn) {
            createDirBtn.addEventListener('click', () => this.showCreateDirectoryModal());
        }

        // Upload file button
        const uploadFileBtn = document.getElementById('uploadFileBtn');
        if (uploadFileBtn) {
            uploadFileBtn.addEventListener('click', () => this.showUploadFileModal());
        }

        // Search
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => this.searchItems(e.target.value));
        }

        // Modal close buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-close')) {
                e.target.closest('.modal-overlay').remove();
            }
            if (e.target.classList.contains('modal-overlay')) {
                e.target.remove();
            }
        });

        // Drag and drop
        this.setupDragAndDrop();

        // Double click to open
        document.addEventListener('dblclick', (e) => {
            if (e.target.closest('.item')) {
                const item = e.target.closest('.item');
                const itemId = item.dataset.itemId;
                const itemType = item.dataset.itemType;
                this.handleDoubleClick(itemId, itemType);
            }
        });
    }

    setupAJAX() {
        document.addEventListener('submit', (e) => {
            if (e.target.dataset.ajax) {
                e.preventDefault();
                this.submitForm(e.target);
            }
        });
    }

    loadRootDirectory() {
        this.fetchDirectory(null);
    }

    navigateToDirectory(directoryId) {
        this.currentDirectoryId = directoryId;
        this.fetchDirectory(directoryId);
    }

    fetchDirectory(directoryId) {
        this.showLoading();

        fetch('api/directory.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get&directory_id=' + (directoryId || 'null')
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.currentPath = data.breadcrumb || [];
                    this.renderBreadcrumb();
                    this.renderItems(data.directories, data.documents);
                } else {
                    this.showAlert('error', data.message || 'Failed to load directory');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showAlert('error', 'An error occurred');
            })
            .finally(() => this.hideLoading());
    }

    renderItems(directories, documents) {
        const container = document.getElementById('itemsContainer');
        if (!container) return;

        if (directories.length === 0 && documents.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-title">No items</div>
                    <div class="empty-state-text">This directory is empty. Create a new folder or upload a file.</div>
                </div>
            `;
            return;
        }

        let html = '';

        directories.forEach(dir => {
            html += this.createDirectoryElement(dir);
        });

        documents.forEach(doc => {
            html += this.createDocumentElement(doc);
        });

        container.innerHTML = html;
        this.attachItemEventListeners();
    }

    createDirectoryElement(dir) {
        return `
            <div class="item" data-item-id="${dir.id}" data-item-type="directory" data-dir-id="${dir.id}">
                <div class="item-icon">📁</div>
                <div class="item-name">${this.escapeHtml(dir.name)}</div>
                <div class="item-meta">Folder</div>
                <div class="item-actions">
                    <button class="item-btn item-btn-edit" onclick="archiveManager.editDirectory(${dir.id}, '${this.escapeHtml(dir.name)}'); event.stopPropagation();">Edit</button>
                    <button class="item-btn item-btn-delete" onclick="archiveManager.deleteDirectory(${dir.id}); event.stopPropagation();">Delete</button>
                </div>
            </div>
        `;
    }

    createDocumentElement(doc) {
        const icon = this.getFileIcon(doc.file_type);
        const size = this.formatFileSize(doc.file_size);
        return `
            <div class="item" data-item-id="${doc.id}" data-item-type="document" data-doc-id="${doc.id}">
                <div class="item-icon">${icon}</div>
                <div class="item-name">${this.escapeHtml(doc.name)}</div>
                <div class="item-meta">${size}</div>
                <div class="item-actions">
                    <button class="item-btn item-btn-edit" onclick="archiveManager.editDocument(${doc.id}, '${this.escapeHtml(doc.name)}'); event.stopPropagation();">Edit</button>
                    <button class="item-btn item-btn-delete" onclick="archiveManager.deleteDocument(${doc.id}); event.stopPropagation();">Delete</button>
                </div>
            </div>
        `;
    }

    attachItemEventListeners() {
        document.querySelectorAll('.item').forEach(item => {
            item.addEventListener('dblclick', (e) => {
                if (!e.target.closest('.item-actions')) {
                    const itemId = item.dataset.itemId;
                    const itemType = item.dataset.itemType;
                    this.handleDoubleClick(itemId, itemType);
                }
            });
        });
    }

    handleDoubleClick(itemId, itemType) {
        if (itemType === 'directory') {
            this.navigateToDirectory(itemId);
        } else if (itemType === 'document') {
            this.openDocument(itemId);
        }
    }

    openDocument(docId) {
        fetch('api/document.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get&id=' + docId
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.file_path) {
                    window.open(data.data.file_path, '_blank');
                } else {
                    this.showAlert('error', 'Cannot open this file');
                }
            })
            .catch(error => console.error('Error:', error));
    }

    renderBreadcrumb() {
        const breadcrumbContainer = document.getElementById('breadcrumb');
        if (!breadcrumbContainer) return;

        let html = '<span class="breadcrumb-item active" onclick="archiveManager.navigateToDirectory(null)">📦 Home</span>';

        this.currentPath.forEach(dir => {
            html += `<span class="breadcrumb-separator">/</span>`;
            html += `<span class="breadcrumb-item" onclick="archiveManager.navigateToDirectory(${dir.id})">${this.escapeHtml(dir.name)}</span>`;
        });

        breadcrumbContainer.innerHTML = html;
    }

    showCreateDirectoryModal() {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <div class="modal-title">Create New Directory</div>
                    <button class="modal-close">✕</button>
                </div>
                <form data-ajax class="modal-body">
                    <input type="hidden" name="action" value="create-directory">
                    <input type="hidden" name="parent_id" value="${this.currentDirectoryId || 'null'}">
                    <div class="form-group">
                        <label for="dirName">Directory Name</label>
                        <input type="text" id="dirName" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="dirDesc">Description</label>
                        <textarea id="dirDesc" name="description"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);
        document.getElementById('dirName').focus();
    }

    showUploadFileModal() {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <div class="modal-title">Upload File</div>
                    <button class="modal-close">✕</button>
                </div>
                <form data-ajax enctype="multipart/form-data" class="modal-body">
                    <input type="hidden" name="action" value="upload-document">
                    <input type="hidden" name="directory_id" value="${this.currentDirectoryId || 'null'}">
                    <div class="upload-zone" id="uploadZone">
                        <div class="upload-icon">📤</div>
                        <div class="upload-text">Drag files here or <strong>click to select</strong></div>
                        <input type="file" name="file" id="fileInput" style="display: none;" required>
                    </div>
                    <div id="fileNameDisplay" style="margin-top: 15px; display: none;">
                        <p><strong>Selected file:</strong> <span id="selectedFileName"></span></p>
                    </div>
                    <div class="form-group" style="margin-top: 15px;">
                        <label for="docDesc">Description</label>
                        <textarea id="docDesc" name="description"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        const uploadZone = modal.querySelector('#uploadZone');
        const fileInput = modal.querySelector('#fileInput');
        const fileNameDisplay = modal.querySelector('#fileNameDisplay');
        const selectedFileName = modal.querySelector('#selectedFileName');

        uploadZone.addEventListener('click', () => fileInput.click());

        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragging');
        });

        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragging');
        });

        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragging');
            fileInput.files = e.dataTransfer.files;
            this.updateFileDisplay(e.dataTransfer.files[0], fileNameDisplay, selectedFileName);
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.updateFileDisplay(e.target.files[0], fileNameDisplay, selectedFileName);
            }
        });
    }

    updateFileDisplay(file, display, nameElement) {
        display.style.display = 'block';
        nameElement.textContent = file.name + ' (' + this.formatFileSize(file.size) + ')';
    }

    editDirectory(dirId, dirName) {
        const newName = prompt('Edit directory name:', dirName);
        if (newName === null) return;

        this.apiCall('POST', 'api/directory.php', {
            action: 'update',
            id: dirId,
            name: newName
        }, () => {
            this.showAlert('success', 'Directory updated');
            this.fetchDirectory(this.currentDirectoryId);
        });
    }

    deleteDirectory(dirId) {
        if (!confirm('Are you sure you want to delete this directory and all its contents?')) {
            return;
        }

        this.apiCall('POST', 'api/directory.php', {
            action: 'delete',
            id: dirId
        }, () => {
            this.showAlert('success', 'Directory deleted');
            this.fetchDirectory(this.currentDirectoryId);
        });
    }

    editDocument(docId, docName) {
        const newName = prompt('Edit document name:', docName);
        if (newName === null) return;

        this.apiCall('POST', 'api/document.php', {
            action: 'update',
            id: docId,
            name: newName
        }, () => {
            this.showAlert('success', 'Document updated');
            this.fetchDirectory(this.currentDirectoryId);
        });
    }

    deleteDocument(docId) {
        if (!confirm('Are you sure you want to delete this document?')) {
            return;
        }

        this.apiCall('POST', 'api/document.php', {
            action: 'delete',
            id: docId
        }, () => {
            this.showAlert('success', 'Document deleted');
            this.fetchDirectory(this.currentDirectoryId);
        });
    }

    submitForm(form) {
        const formData = new FormData(form);
        const action = formData.get('action');

        let endpoint = 'api/';
        if (action.includes('directory')) {
            endpoint += 'directory.php';
        } else if (action.includes('document')) {
            endpoint += 'document.php';
        }

        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.showAlert('success', data.message || 'Operation successful');
                    form.closest('.modal-overlay')?.remove();
                    this.fetchDirectory(this.currentDirectoryId);
                } else {
                    this.showAlert('error', data.message || 'Operation failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showAlert('error', 'An error occurred');
            });
    }

    searchItems(query) {
        if (!query.trim()) {
            this.fetchDirectory(this.currentDirectoryId);
            return;
        }

        this.showLoading();

        fetch('api/search.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'query=' + encodeURIComponent(query)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const container = document.getElementById('itemsContainer');
                    const headerTitle = document.querySelector('.header-title');

                    headerTitle.textContent = 'Search Results: ' + query;

                    if (data.directories.length === 0 && data.documents.length === 0) {
                        container.innerHTML = `
                            <div class="empty-state" style="grid-column: 1 / -1;">
                                <div class="empty-state-icon">🔍</div>
                                <div class="empty-state-title">No results found</div>
                                <div class="empty-state-text">Try a different search term</div>
                            </div>
                        `;
                    } else {
                        this.renderItems(data.directories, data.documents);
                    }
                }
            })
            .catch(error => console.error('Error:', error))
            .finally(() => this.hideLoading());
    }

    setupDragAndDrop() {
        const contentArea = document.querySelector('.content');
        if (!contentArea) return;

        contentArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            contentArea.classList.add('draggable-over');
        });

        contentArea.addEventListener('dragleave', () => {
            contentArea.classList.remove('draggable-over');
        });

        contentArea.addEventListener('drop', (e) => {
            e.preventDefault();
            contentArea.classList.remove('draggable-over');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.uploadFiles(files);
            }
        });
    }

    uploadFiles(files) {
        Array.from(files).forEach(file => {
            const formData = new FormData();
            formData.append('action', 'upload-document');
            formData.append('file', file);
            formData.append('directory_id', this.currentDirectoryId || 'null');

            fetch('api/document.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.showAlert('success', file.name + ' uploaded');
                        this.fetchDirectory(this.currentDirectoryId);
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    }

    apiCall(method, endpoint, data, callback) {
        const body = method === 'GET' ? null : new URLSearchParams(data);

        fetch(endpoint, {
            method: method,
            headers: method === 'POST' ? {
                'Content-Type': 'application/x-www-form-urlencoded',
            } : {},
            body: body
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    callback(data);
                } else {
                    this.showAlert('error', data.message || 'Operation failed');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.showAlert('error', 'An error occurred');
            });
    }

    showLoading() {
        const container = document.getElementById('itemsContainer');
        if (container) {
            container.innerHTML = `
                <div class="loading" style="grid-column: 1 / -1;">
                    <div class="spinner"></div> Loading...
                </div>
            `;
        }
    }

    hideLoading() {}

    showAlert(type, message) {
        const alertContainer = document.getElementById('alertContainer') || this.createAlertContainer();
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type;
        alert.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: inherit; cursor: pointer; font-size: 18px;">✕</button>
        `;

        alertContainer.appendChild(alert);

        setTimeout(() => alert.remove(), 5000);
    }

    createAlertContainer() {
        const container = document.createElement('div');
        container.id = 'alertContainer';
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '999';
        container.style.maxWidth = '400px';
        document.body.appendChild(container);
        return container;
    }

    getFileIcon(fileType) {
        const iconMap = {
            'pdf': '📄',
            'doc': '📝',
            'docx': '📝',
            'xlsx': '📊',
            'xls': '📊',
            'ppt': '🎯',
            'pptx': '🎯',
            'zip': '📦',
            'jpg': '🖼️',
            'jpeg': '🖼️',
            'png': '🖼️',
            'gif': '🖼️',
            'txt': '📃',
        };
        return iconMap[fileType?.toLowerCase()] || '📄';
    }

    formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const units = ['B', 'KB', 'MB', 'GB'];
        const index = Math.floor(Math.log(bytes) / Math.log(1024));
        return (bytes / Math.pow(1024, index)).toFixed(2) + ' ' + units[index];
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

let archiveManager;
document.addEventListener('DOMContentLoaded', () => {
    archiveManager = new ArchiveManager();
});
