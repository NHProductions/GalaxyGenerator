

<head>
    <style>
    body {
    background-color: black;
	}
	
    #controls {
	background-color: gray;
	width: 12.5%;
	height: 100%;
	left: 0%;
	top: 0%;
	text-align: center;
	}
	#stars {
	width: 87.5%;
	height: 100%;
	position: absolute;
	left: 10%;
	top: 0%;
	}
    </style>
    
    
    
    
    
    
</head>

<body id="body"> 
    
    
    <div id="controls"> 
        <p> Controls: </p>
            
        <label for="staramt" id="starAmountLabel"> Star Amount (10000): </label> <br />
        <input type="range" id="staramt" min=1 value=10000 max=30000> </input> <br />
        <label for="age" id="galaxyAge"> Galaxy Age (4 Byr) </label> <br />
        <input type="range" id="age" min=0 value=4 max=50 step=1> </input> <br />
            
        <div id="spiral_controls"> 
            
            <label for="armAmount"> Arm Amount </label> <br />
            <input id="armAmount" type="number" min="0" max="10" value="3"> </input> <br />
            <label for="bulgeDensity"> Bulge Density (%) </label> <br />
            <input id="bulgeDensity" type="number" min="0" max="100" value="40"> </input> <br />
            <label for="middleDensity"> Middle Density (%) </label> <br />
            <input id="middleDensity" type="number" min="0" max="100" value="10"> </input> <br />
       		<label for="spiralDensity"> Spiral Density (%) </label> <br />
            <input id="spiralDensity" type="number" min="0" max="100" value="49"> </input> <br />
       		<label for="otherDensity"> Spiral Density (%) </label> <br />
            <input id="otherDensity" type="number" min="0" max="100" value="1"> </input> <br />
       		<label for="innerRadius"> Inner Radius Size (r0) </label> <br />
            <input type="number" min="1" max="200" id="innerRadius" value="130"> </input> <br />
            <label for="slope"> Amplitude (k) </label> <br />
            <input id="slope" type="number" min="0.01" max="3" value="0.1"> </input> <br />
            <label for="Color"> Give Stars colors based on age? </label> <br />
            <input id="starColors" checked="true" type="checkbox"> </input> <br />
	       <button onClick="getSpiralStars()" id="starSubmit"> Submit </button>
       </div>
       
    </div>
    <div id="stars">
    </div>
    <script>
    document.getElementById("staramt").addEventListener("input", function() {
    document.getElementById("starAmountLabel").innerHTML = "Star Amount (" + document.getElementById("staramt").value + ")";
    });
	document.getElementById("age").addEventListener("input", function() {
    document.getElementById("galaxyAge").innerHTML = "Galaxy Age (" + document.getElementById("age").value + " Byr)";
    });

    var rect = document.getElementById("stars").getBoundingClientRect();
    var scrDim = [rect.width,rect.height];
	var center = [scrDim[0]/2,scrDim[1]/2];
    async function getSpiralStars() {
    var giveColors = document.getElementById('starColors').checked;
    const stars = document.querySelectorAll(`[id="star"]`);
  	stars.forEach(element => {
    element.remove();
  	});
    var data2 = {
        armsAmount: document.getElementById("armAmount").value,
        bulgeDensity: document.getElementById("bulgeDensity").value,
        middleDensity: document.getElementById("middleDensity").value,
        spiralDensity: document.getElementById("spiralDensity").value,
        otherDensity: document.getElementById("otherDensity").value,
    	r0: document.getElementById("innerRadius").value,
    	k: document.getElementById("slope").value,
        age: document.getElementById("age").value,
        staramt: document.getElementById("staramt").value,
    }
        
    console.log(JSON.stringify(data2));
    var req = await fetch('stars.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data2)
    });
    //console.log(req.text())
	var json = await req.json().then(data => {
        
        data.forEach((star) => {
            console.log(star);
            var pt = star['Position'];
            var modifiedPosition = [center[0]+pt[0],center[1]+pt[1]];
            var newElement = document.createElement("div");
            newElement.classList.add('circle');
            newElement.style = `width: 1px; height: 1px;`;
            newElement.style.position = "absolute";
            newElement.style.top = modifiedPosition[1]+"px";
            newElement.style.right = modifiedPosition[0]+"px"; 
            if (giveColors) {
            newElement.style.backgroundColor = `rgb(${star['Color'][0]}, ${star['Color'][1]}, ${star['Color'][2] } ) `; 
            }
            else {
                newElement.style.backgroundColor = "white";
            }
            newElement.style.borderRadius = "50%";
            newElement.id = "star";
			document.getElementById("stars").appendChild(newElement);
        });
        
        
        
        
      })
    }








    </script>
    
</body>