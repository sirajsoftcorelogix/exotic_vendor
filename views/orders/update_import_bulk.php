<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Order Status Update</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .progress-bar {
            transition: width 0.3s ease;
        }
        .status-log {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            padding: 1rem;
            background-color: #f9fafb;
        }
        .log-entry {
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }
        .log-success {
            color: #059669;
        }
        .log-error {
            color: #dc2626;
        }
        .log-info {
            color: #2563eb;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Bulk Order Status Update</h1>
            <p class="text-gray-600 mb-6">Processing orders in batches. This will automatically continue until complete.</p>

            <!-- Progress Section -->
            <div class="mb-8">
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-semibold text-gray-700">Overall Progress</label>
                    <span class="text-sm font-bold text-orange-600"><span id="progressPercent">0</span>% (<span id="importedCount">0</span> / <span id="maxLimit">5000</span>)</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full overflow-hidden">
                    <div id="progressBar" class="progress-bar bg-orange-600 h-6 flex items-center justify-center text-white text-xs font-bold" style="width: 0%">
                        0%
                    </div>
                </div>
            </div>

            <!-- Status Messages -->
            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Status Messages</h2>
                <div id="statusLog" class="status-log">
                    <div class="log-entry log-info">Initializing batch processing...</div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="grid grid-cols-2 gap-4 mb-8">
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <p class="text-sm text-gray-600 font-semibold">Current Batch</p>
                    <p class="text-2xl font-bold text-blue-600"><span id="batchImported">0</span></p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <p class="text-sm text-gray-600 font-semibold">Total Imported Today</p>
                    <p class="text-2xl font-bold text-green-600"><span id="totalImported">0</span></p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                    <p class="text-sm text-gray-600 font-semibold">Batch Items Processed</p>
                    <p class="text-2xl font-bold text-purple-600"><span id="batchTotal">0</span></p>
                </div>
                <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-200">
                    <p class="text-sm text-gray-600 font-semibold">Affected Rows</p>
                    <p class="text-2xl font-bold text-indigo-600"><span id="affectedRows">0</span></p>
                </div>
                <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
                    <p class="text-sm text-gray-600 font-semibold">Status</p>
                    <p class="text-lg font-bold text-orange-600"><span id="statusText">Processing...</span></p>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-4">
                <button id="startBtn" onclick="startProcess()" class="flex-1 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold transition">
                    Start Process
                </button>
                <button id="pauseBtn" onclick="pauseProcess()" class="flex-1 px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 font-semibold transition hidden">
                    Pause
                </button>
                <button id="resumeBtn" onclick="resumeProcess()" class="flex-1 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition hidden">
                    Resume
                </button>
                <button id="stopBtn" onclick="stopProcess()" class="flex-1 px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold transition hidden">
                    Stop
                </button>
                <button id="reloadBtn" onclick="location.reload()" class="flex-1 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold transition hidden">
                    Reload Page
                </button>
            </div>
        </div>
    </div>

    <script>
        // Configuration
        const CONFIG = {
            SECRET_KEY: '<?php echo EXPECTED_SECRET_KEY; ?>',
            MAX_LIMIT: 5000,
            SLEEP_BUFFER: 1000 // Extra 1 second buffer on top of API recommendation
        };

        // State
        let state = {
            isRunning: false,
            isPaused: false,
            totalImported: 0,
            maxLimit: CONFIG.MAX_LIMIT,
            batchImported: 0,
            batchTotal: 0,
            progressPercent: 0,
            completed: false,
            errorCount: 0,
            affectedRows: 0
        };

        // Logging function
        function addLog(message, type = 'info') {
            const statusLog = document.getElementById('statusLog');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = `log-entry log-${type}`;
            logEntry.textContent = `[${timestamp}] ${message}`;
            statusLog.appendChild(logEntry);
            statusLog.scrollTop = statusLog.scrollHeight;
        }

        // Update UI
        function updateUI() {
            document.getElementById('progressPercent').textContent = state.progressPercent;
            document.getElementById('importedCount').textContent = state.totalImported;
            document.getElementById('maxLimit').textContent = state.maxLimit;
            document.getElementById('progressBar').style.width = state.progressPercent + '%';
            document.getElementById('progressBar').textContent = state.progressPercent + '%';
            document.getElementById('batchImported').textContent = state.batchImported;
            document.getElementById('totalImported').textContent = state.totalImported;
            document.getElementById('batchTotal').textContent = state.batchTotal;
            document.getElementById('affectedRows').textContent = state.affectedRows;
            
            if (!state.isRunning && !state.completed) {
                document.getElementById('statusText').textContent = 'Ready';
            } else if (state.isPaused) {
                document.getElementById('statusText').textContent = 'Paused';
            } else if (state.completed) {
                document.getElementById('statusText').textContent = 'Completed';
            } else {
                document.getElementById('statusText').textContent = 'Processing...';
            }
        }

        // Start process
        function startProcess() {
            state.isRunning = true;
            state.isPaused = false;
            state.completed = false;
            
            document.getElementById('startBtn').classList.add('hidden');
            document.getElementById('pauseBtn').classList.remove('hidden');
            document.getElementById('stopBtn').classList.remove('hidden');
            document.getElementById('reloadBtn').classList.add('hidden');
            
            addLog('🚀 Starting bulk order status update...', 'success');
            updateUI();
            processBatch();
        }

        // Process batch
        async function processBatch() {
            if (state.completed || !state.isRunning) {
                return;
            }

            if (state.isPaused) {
                addLog('Process paused. Waiting to resume...', 'info');
                setTimeout(processBatch, 1000);
                return;
            }

            try {
                const url = `index.php?page=orders&action=update_import_bulk&secret_key=${CONFIG.SECRET_KEY}`;
                addLog(`Fetching batch from server...`, 'info');

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
                }

                const data = await response.json();

                if (!data.success) {
                    addLog(`Error: ${data.message || 'Unknown error'}`, 'error');
                    state.errorCount++;
                    state.completed = true;
                    finishProcess();
                    return;
                }

                // Update state from API response
                state.batchImported = data.batch_imported || 0;
                state.batchTotal = data.batch_total_items || 0;
                state.totalImported = data.total_imported || 0;
                state.progressPercent = data.progress_percent || 0;
                state.completed = data.completed || false;
                state.maxLimit = data.max_limit || 5000;
                state.affectedRows = (state.affectedRows || 0) + (data.affected_rows || 0);

                // Log results
                if (state.batchImported > 0) {
                    addLog(`✓ Batch: ${state.batchImported} updated from ${state.batchTotal} items`, 'success');
                } else {
                    addLog(`ℹ Batch: ${state.batchTotal} items checked`, 'info');
                }
                addLog(`Progress: ${state.totalImported} / ${state.maxLimit} records (${state.progressPercent}%)`, 'info');

                // Log errors if any
                if (data.errors && data.errors.length > 0) {
                    data.errors.forEach(error => {
                        addLog(`⚠ API: ${error}`, 'error');
                    });
                    state.errorCount += data.error_count || 0;
                }

                updateUI();

                // Check if completed
                if (state.completed) {
                    addLog('✓ Process completed successfully!', 'success');
                    finishProcess();
                    return;
                }

                // Continue if there are more records or more batches to process
                if (data.next_action) {
                    const waitSeconds = data.next_action.wait_seconds || 5;
                    const waitMs = (waitSeconds * 1000) + CONFIG.SLEEP_BUFFER;
                    
                    addLog(`⏳ Waiting ${waitSeconds}s before next batch...`, 'info');
                    document.getElementById('statusText').textContent = `Waiting (${waitSeconds}s)`;

                    setTimeout(processBatch, waitMs);
                } else if (!state.completed) {
                    // No next_action but not completed - fetch immediately
                    addLog('Fetching next batch immediately...', 'info');
                    setTimeout(processBatch, 100);
                } else {
                    addLog('✓ Process finished.', 'success');
                    finishProcess();
                }

            } catch (error) {
                addLog(`✗ Error: ${error.message}`, 'error');
                state.errorCount++;
                
                // Retry after 5 seconds
                addLog('Retrying in 5 seconds...', 'info');
                setTimeout(processBatch, 5000);
            }
        }

        // Finish process
        function finishProcess() {
            state.isRunning = false;
            state.completed = true;
            document.getElementById('pauseBtn').classList.add('hidden');
            document.getElementById('resumeBtn').classList.add('hidden');
            document.getElementById('stopBtn').classList.add('hidden');
            document.getElementById('reloadBtn').classList.remove('hidden');

            const summary = `✓ Complete: ${state.totalImported} imported, ${state.errorCount} errors`;
            addLog(summary, state.errorCount === 0 ? 'success' : 'error');
            updateUI();
        }

        // Pause process
        function pauseProcess() {
            state.isPaused = true;
            document.getElementById('pauseBtn').classList.add('hidden');
            document.getElementById('resumeBtn').classList.remove('hidden');
            addLog('⏸ Process paused by user.', 'info');
            updateUI();
        }

        // Resume process
        function resumeProcess() {
            state.isPaused = false;
            document.getElementById('pauseBtn').classList.remove('hidden');
            document.getElementById('resumeBtn').classList.add('hidden');
            addLog('▶ Process resumed by user.', 'info');
            updateUI();
            processBatch();
        }

        // Stop process
        function stopProcess() {
            state.isRunning = false;
            document.getElementById('pauseBtn').classList.add('hidden');
            document.getElementById('resumeBtn').classList.add('hidden');
            document.getElementById('stopBtn').classList.add('hidden');
            document.getElementById('reloadBtn').classList.remove('hidden');
            addLog('⏹ Process stopped by user.', 'error');
            updateUI();
        }

        // Initialize UI on page load
        window.addEventListener('load', function() {
            updateUI();
            addLog('Ready to start bulk order status update. Click "Start Process" button to begin.', 'info');
        });

        // Handle page unload
        window.addEventListener('beforeunload', function(e) {
            if (state.isRunning && !state.completed) {
                e.preventDefault();
                e.returnValue = 'Process is still running. Are you sure you want to leave?';
                return false;
            }
        });
    </script>
</body>
</html>
