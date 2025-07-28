<!DOCTYPE html>
<html>
<head>
	<?php include 'head.php';?>
</head>
<body>
	<?php include 'body-header.php'; ?>
	<main>
		<div class="cards">
			<div class="card">
				<img id="ESP32-1">
				<h2 id="cam-one-temp">Updating Temperature...</h2>
				<button onclick="saveVideo()">
					Save Video
				</button>
				<h2 id="time-testing"></h2>
				<!--
				<button id="ESP32-1-Right" onclick="sendMessage(this.id)">Left</button>
				<button id="ESP32-1-Left" onclick="sendMessage(this.id)">Right</button>
				<button id="ESP32-1-Up" onclick="sendMessage(this.id)">Up</button>
				<button id="ESP32-1-Down" onclick="sendMessage(this.id)">Down</button>
				-->
			</div>
			<!--  -->
			<!-- 
			<div class="card">
				<img id="ESP32-2" src="/">
				<button id="ESP32-2-Right" onclick="sendMessage(this.id)">Right</button>
				<button id="ESP32-2-Left" onclick="sendMessage(this.id)">Left</button>
				<button id="ESP32-2-Up" onclick="sendMessage(this.id)">Up</button>
				<button id="ESP32-2-Down" onclick="sendMessage(this.id)">Down</button>
			</div>
			<div class="card">
				<img id="ESP32-3" src="/">
				<button id="ESP32-3-Right" onclick="sendMessage(this.id)">Right</button>
				<button id="ESP32-3-Left" onclick="sendMessage(this.id)">Left</button>
				<button id="ESP32-3-Up" onclick="sendMessage(this.id)">Up</button>
				<button id="ESP32-3-Down" onclick="sendMessage(this.id)">Down</button>
			</div> 
			<button id="Reset Cameras" onclick="sendMessage(this.id)">Reset Cameras</button>
			-->
		</div>
	</main>
	<script type="text/javascript">
		const img_1 = document.getElementById("ESP32-1");
		// const img_2 = document.getElementById("ESP32-2");
		// const img_3 = document.getElementById("ESP32-3");
		var imageFrame;
		const cam_one_temp_html = document.getElementById("cam-one-temp");
		const title_temp = document.getElementById("title-temp");
		let urlObject;

		let socket;
		let serverUrl;
		const reconnectInterval = 5000; // Constant time before attempting reconnect

		function connectWebSocket() {
		    socket = new WebSocket(serverUrl);
		    getTemp();

		    socket.onopen = () => {
		        console.log('Connected to', serverUrl);
				socket.send("WEB_CLIENT");
		    };

		    socket.onmessage = async (message) => {
				const arrayBuffer = message.data;

				if(urlObject){
					URL.revokeObjectURL(urlObject);
				}

				var blobObj = new Blob([arrayBuffer]);
				const msg = await blobObj.text();

				if(msg.length > 64) {
					const buf = await blobObj.arrayBuffer();

					var uint8 = new Uint8Array(buf.slice(12, 13));
					var currentCam = uint8[0];

					if(currentCam == 1){
						imageFrame = img_1;
					} else if(currentCam == 2) {
						imageFrame = img_2;
					} else {
						imageFrame = img_3;
					}

					urlObject = URL.createObjectURL(blobObj);
					imageFrame.src = urlObject;
				} else if(msg.indexOf("cam_one_temp") != -1) {
					var deviceName = msg.substring(0, 7);

					tempString = msg.replace("cam_one_temp_", "The current temperature is ")+"°F";
					titleString = msg.replace("cam_one_temp_", "")+"°F  - Blueberry Client";
					cam_one_temp_html.innerHTML = tempString;
					title_temp.innerHTML = titleString;
					console.log(tempString);
				}
			}

		    socket.onerror = (error) => {
		        console.error("WebSocket error:", error);
		        socket.close(); // Ensure the connection is closed before reconnecting
		    };

		    socket.onclose = () => {
		        console.warn(`WebSocket closed. Reconnecting in ${reconnectInterval / 1000} seconds...`);
		        setTimeout(connectWebSocket, reconnectInterval); // Constant retry interval
		    };
		}

		async function getSecretKey() {
	        try {
	            const response = await fetch('/config', {
	                method: 'POST',
	                headers: { 'Content-Type': 'application/json' },
	                body: JSON.stringify({ requestKey: 'my-client-request' }) 
	            });

	            const data = await response.json();
	            serverUrl = data.WS_URL;
	        	connectWebSocket();
	        } catch (error) {
	            console.error('Error:', error);
	        }
	    }

	    async function getTemp() {
	        try {
	            const response = await fetch('/temp', {
	                method: 'POST',
	                headers: { 'Content-Type': 'application/json' },
	                body: JSON.stringify({ requestKey: 'my-client-request-temp' }) 
	            });

	            const data = await response.json();
	            tempString = `The current temperature is ${data['temperatureBuffer']['cam_one']}°F`;
				cam_one_temp_html.innerHTML = tempString;
	            console.log(tempString);
	            
	            titleString = `${data['temperatureBuffer']['cam_one']}°F - Blueberry Client`;
				title_temp.innerHTML = titleString;
	        } catch (error) {
	            console.error('Error:', error);
	        }
	    }

	    async function saveVideo() {
	        try {
	            const response = await fetch('/save-video', {
	                method: 'POST',
	                headers: { 'Content-Type': 'application/json' },
	                body: JSON.stringify({ requestKey: 'my-client-request-save-video' }) 
	            });

	            const data = await response.json();
	            console.log(data.response);

	        } catch (error) {
	            console.error('Error:', error);
	        }
	    }
	    getSecretKey();
		// Start the WebSocket connection
		// connectWebSocket();
	</script>
</body>
</html>