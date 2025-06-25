<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Test - MuciRadio</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            line-height: 1.6;
        }
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-radius: 10px;
            border-left: 4px solid #FF6B35;
        }
        .stream-test {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .controls {
            margin: 10px 0;
        }
        button {
            background: #FF6B35;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #e55a2e;
        }
        .status {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1>🎵 Audio Test Page</h1>
    <p>This page helps diagnose audio playback issues with radio streams.</p>

    <div class="test-section">
        <h3>Test Simple Audio File</h3>
        <div class="stream-test">
            <h4>Basic MP3 Test</h4>
            <audio id="testAudio1" controls>
                <source src="https://www.soundjay.com/misc/sounds/beep-07a.mp3" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
            <div class="controls">
                <button onclick="testPlay('testAudio1')">Play</button>
                <button onclick="testStop('testAudio1')">Stop</button>
            </div>
            <div id="status1" class="status info">Ready to test</div>
        </div>
    </div>

    <div class="test-section">
        <h3>Test Radio Streams</h3>
        
        <div class="stream-test">
            <h4>KCRW (Known Working)</h4>
            <audio id="testAudio2" preload="none">
                <source src="https://kcrw.streamguys1.com/kcrw_192k_mp3_on_air" type="audio/mpeg">
            </audio>
            <div class="controls">
                <button onclick="testStreamPlay('testAudio2', 'https://kcrw.streamguys1.com/kcrw_192k_mp3_on_air')">Play KCRW</button>
                <button onclick="testStop('testAudio2')">Stop</button>
            </div>
            <div id="status2" class="status info">Ready to test</div>
        </div>

        <div class="stream-test">
            <h4>SomaFM Groove Salad</h4>
            <audio id="testAudio3" preload="none">
                <source src="https://ice1.somafm.com/groovesalad-128-mp3" type="audio/mpeg">
            </audio>
            <div class="controls">
                <button onclick="testStreamPlay('testAudio3', 'https://ice1.somafm.com/groovesalad-128-mp3')">Play SomaFM</button>
                <button onclick="testStop('testAudio3')">Stop</button>
            </div>
            <div id="status3" class="status info">Ready to test</div>
        </div>

        <div class="stream-test">
            <h4>Radio Swiss Pop</h4>
            <audio id="testAudio4" preload="none">
                <source src="https://stream.srg-ssr.ch/rsp/mp3_128" type="audio/mpeg">
            </audio>
            <div class="controls">
                <button onclick="testStreamPlay('testAudio4', 'https://stream.srg-ssr.ch/rsp/mp3_128')">Play Radio Swiss</button>
                <button onclick="testStop('testAudio4')">Stop</button>
            </div>
            <div id="status4" class="status info">Ready to test</div>
        </div>

        <div class="stream-test">
            <h4>181.FM - The Box</h4>
            <audio id="testAudio5" preload="none">
                <source src="https://listen.181fm.com/181-thebox_128k.mp3" type="audio/mpeg">
            </audio>
            <div class="controls">
                <button onclick="testStreamPlay('testAudio5', 'https://listen.181fm.com/181-thebox_128k.mp3')">Play 181.FM</button>
                <button onclick="testStop('testAudio5')">Stop</button>
            </div>
            <div id="status5" class="status info">Ready to test</div>
        </div>
    </div>

    <div class="test-section">
        <h3>Browser Information</h3>
        <div id="browserInfo"></div>
    </div>

    <div class="test-section">
        <h3>Console Logs</h3>
        <pre id="logs"></pre>
        <button onclick="clearLogs()">Clear Logs</button>
    </div>

    <div class="test-section">
        <h3>Quick Fixes</h3>
        <ul>
            <li><strong>HTTPS Required:</strong> Try accessing via HTTPS if available</li>
            <li><strong>Allow Autoplay:</strong> Enable autoplay in browser settings</li>
            <li><strong>Mixed Content:</strong> Ensure all resources use HTTPS</li>
            <li><strong>CORS Issues:</strong> Some streams block web browser access</li>
        </ul>
        
        <h4>Working Stream URLs to Try:</h4>
        <ul>
            <li><code>https://ice1.somafm.com/groovesalad-128-mp3</code></li>
            <li><code>https://kcrw.streamguys1.com/kcrw_192k_mp3_on_air</code></li>
            <li><code>https://stream.srg-ssr.ch/rsp/mp3_128</code></li>
            <li><code>https://listen.181fm.com/181-thebox_128k.mp3</code></li>
        </ul>
    </div>

    <script>
        let logs = [];

        function log(message) {
            const timestamp = new Date().toLocaleTimeString();
            logs.push(`[${timestamp}] ${message}`);
            document.getElementById('logs').textContent = logs.join('\n');
            console.log(message);
        }

        function clearLogs() {
            logs = [];
            document.getElementById('logs').textContent = '';
        }

        function updateStatus(audioId, message, type = 'info') {
            const statusId = audioId.replace('testAudio', 'status');
            const statusEl = document.getElementById(statusId);
            statusEl.textContent = message;
            statusEl.className = `status ${type}`;
        }

        function testPlay(audioId) {
            const audio = document.getElementById(audioId);
            log(`Testing basic play for ${audioId}`);
            
            audio.addEventListener('loadstart', () => {
                log(`${audioId}: Load started`);
                updateStatus(audioId, 'Loading...', 'info');
            });

            audio.addEventListener('canplay', () => {
                log(`${audioId}: Can play`);
                updateStatus(audioId, 'Ready to play', 'success');
            });

            audio.addEventListener('playing', () => {
                log(`${audioId}: Playing`);
                updateStatus(audioId, 'Playing ♪', 'success');
            });

            audio.addEventListener('error', (e) => {
                log(`${audioId}: Error - ${e.target.error?.message || 'Unknown error'}`);
                updateStatus(audioId, 'Error: ' + (e.target.error?.message || 'Failed to load'), 'error');
            });

            audio.play().catch(error => {
                log(`${audioId}: Play failed - ${error.message}`);
                updateStatus(audioId, 'Play failed: ' + error.message, 'error');
            });
        }

        function testStreamPlay(audioId, streamUrl) {
            const audio = document.getElementById(audioId);
            log(`Testing stream play for ${audioId}: ${streamUrl}`);
            
            // Set up event listeners
            audio.addEventListener('loadstart', () => {
                log(`${audioId}: Stream load started`);
                updateStatus(audioId, 'Connecting to stream...', 'info');
            });

            audio.addEventListener('canplay', () => {
                log(`${audioId}: Stream can play`);
                updateStatus(audioId, 'Stream ready', 'success');
            });

            audio.addEventListener('playing', () => {
                log(`${audioId}: Stream playing`);
                updateStatus(audioId, 'Streaming ♪', 'success');
            });

            audio.addEventListener('error', (e) => {
                const errorMsg = e.target.error?.message || 'Stream connection failed';
                log(`${audioId}: Stream error - ${errorMsg}`);
                updateStatus(audioId, 'Stream error: ' + errorMsg, 'error');
            });

            audio.addEventListener('stalled', () => {
                log(`${audioId}: Stream stalled`);
                updateStatus(audioId, 'Stream stalled...', 'error');
            });

            // Set source and play
            audio.src = streamUrl;
            audio.load();
            
            audio.play().catch(error => {
                log(`${audioId}: Stream play failed - ${error.message}`);
                updateStatus(audioId, 'Stream play failed: ' + error.message, 'error');
            });
        }

        function testStop(audioId) {
            const audio = document.getElementById(audioId);
            audio.pause();
            audio.currentTime = 0;
            log(`${audioId}: Stopped`);
            updateStatus(audioId, 'Stopped', 'info');
        }

        // Display browser information
        document.addEventListener('DOMContentLoaded', function() {
            const browserInfo = document.getElementById('browserInfo');
            browserInfo.innerHTML = `
                <strong>User Agent:</strong> ${navigator.userAgent}<br>
                <strong>Platform:</strong> ${navigator.platform}<br>
                <strong>Language:</strong> ${navigator.language}<br>
                <strong>Online:</strong> ${navigator.onLine}<br>
                <strong>Protocol:</strong> ${location.protocol}<br>
                <strong>Host:</strong> ${location.host}<br>
                <strong>Audio Support:</strong> ${!!window.Audio}<br>
                <strong>Media Devices:</strong> ${!!navigator.mediaDevices}
            `;
            
            log('Audio test page loaded');
            log(`Browser: ${navigator.userAgent}`);
            log(`Protocol: ${location.protocol}`);
        });
    </script>
</body>
</html>
