<?php 
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    $r0 = $data != null ? $data['r0'] : 70;
	$k = $data != null ? $data['k'] : 1;
	$thetaStart = 0;
	$thetaEnd = 1.85*M_PI;
	$thetaStep = 1;
	$arm_thickness = 0.1*$r0;
	$armsn = $data != null ? $data['armsAmount'] : 2;
	$galaxyType = "spiral";
	$galaxyAge = $data != null ? $data['age'] : 8;
	$staramt = $data != null ? $data['staramt'] : 1000;
	// global variables
    // represents a spiral galaxy by using a modified logarithmic spiral (r=a*e^btheta)
	class Star {
        public $Position;
        public $Color;
        public $Radius;
        public $Age;
        public $Region;
        public $Theta;
        public $Phase;
        public function __construct($Position, $Color, $Radius, $Region) {
            $this->Position = $Position;
            $this->Color = $Color;
            $this->Radius = $Radius;
        	$this->Age = 0;
        }
    }
	function gaussian($mean = 0, $std = 1) {
        $u = (mt_rand() + 1) / (mt_getrandmax() + 2);
    	$v = (mt_rand() + 1) / (mt_getrandmax() + 2);
    	return $mean + $std * sqrt(-2 * log($u)) * cos(2 * M_PI * $v);
    }
	function random_float($min, $max) {
    return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}
	function sampleDiskRadius($scaleLength) {
    $u = mt_rand() / mt_getrandmax();
    return -$scaleLength * log(1 - $u);
	}
	function clamp($value, $min, $max) {
        if ($value > $max) {
            return $max;
        }
        else if ($value < $min) {
            return $min;
        }
        return $value;
    }
	class StarType {
        public $Density;
        public $Radius;
        public $Color;
        public function __construct($Density, $Radius, $AverageColor) {
            $this->Density = $Density;
            $this->Radius = $Radius;
            $this->Color = $AverageColor;
        }
    }
	/*
        Star Types:
        Main Sequence (90%)
        - O (Purple/Blue) - 1% (5px)
        - B (Light blue) - 0.12% (5px)
        - A (Blue-Gray) - 0.61% (4px)
        - F (Yellow-Gray) - 3% (3px)
        - G (Yellow) - 7.6% (3px)
        - K (Pink-Red) - 12% (px)
        - M (Red) - 75% (2px)
        
        White Dwarves (8.75%) (1px)
        Red Giants (0.5%) (7px)
        Supergiants (0.1%) (10px)
    */
	$StarChances = [
            "O" => new StarType(0.9, 5, [0,228,228]),
            "B" => new StarType(0.1, 5, [0,0,255]),
        	"A" => new StarType(0.5, 4, [128,50,50]),
        	"F" => new StarType(2.7, 3, [128,128,50]),
        	"G" => new StarType(6.84,3,[196,196,0]),
        	"K" => new StarType(10.8,3,[150,50,50]),
        	"M" => new StarType(67.5,2,[196,0,0]),
        	"WhiteDwarf" => new StarType(10.06,1,[200,200,200]),
        	"RedGiant" => new StarType(0.5,7,[225,0,0]),
        	"Supergiant" => new StarType(0.1,10,[255,0,0]),
        ];
	function ColorNoise($DefaultColor) {
        $toReturn = [$DefaultColor[0] + gaussian(0, 7), $DefaultColor[1] + gaussian(0, 7), $DefaultColor[2] + gaussian(0, 7)];
    	return [clamp($toReturn[0],0,255),clamp($toReturn[1], 0, 255), clamp($toReturn[2], 0, 255)];
    }
	function getBulgeStarType($star) {
        // bulge region has mostly O, B, Red giants, and supergiants.
        $BulgeChances = [
            "O" => new StarType(25,5,[0,228,228]),
            "B" => new StarType(50,5,[0,0,255]),
            "WhiteDwarf" => new StarType(95,7,[255,255,255]),
            "RedGiant" => new StarType(99,7,[225,0,0]),
            "Supergiant" => new StarType(100,10,[255,0,0]),
        ];
        $starChoice = random_int(0,100);
        foreach ($BulgeChances as $name => $type) {
            if ($starChoice <= $type->Density) {
                $star->Radius = $type->Radius;
                
                if ($star->Age > 30 && random_int(0, $star->Age) >= 10) {
                	$dark = [0, 0, 0];
                    $star->Color = $dark;
                	break;
                }
                else {
                    $star->Color = $type->Color;
                    break;
                }
                
            }
        }
    }
	function getMiddleStarType($star) {
        $starChoice = random_int(0,100);
        if ($star->Age > 8) {
            $whiteDwarfPercentage = 600/$star->Age;
            $RedGiantPercentage = 0.99*(100-$whiteDwarfPercentage);
            $SuperGiantPercentage = 100-$RedGiantPercentage;
            $OldStarChances = [
                "WhiteDwarf" => new StarType(80,7,[255,255,255]),
            	"RedGiant" => new StarType(97,7,[225,0,0]),
            	"Supergiant" => new StarType(100,10,[255,0,0]),
            ];
            foreach ($OldStarChances as $name => $type) {
            if ($starChoice <= $type->Density) {
                $star->Radius = $type->Radius;
                
                if ($star->Age > 30 && random_int(0, $star->Age) >= 10) {
                	$dark = [0, 0, 0];
                    $star->Color = $dark;
                }
                else {
                    $star->Color = $type->Color;
               	}
                break;
            	}
        	}
        }
        else {
            $starChoice = random_int(0, 100);
            $YoungStarChances = [
                "O" => new StarType(1, 5, [0,228,228]),
            	"B" => new StarType(2, 5, [0,0,255]),
        		"A" => new StarType(3, 4, [128,50,50]),
        		"F" => new StarType(6, 3, [128,128,50]),
        		"G" => new StarType(13,3,[196,196,0]),
        		"K" => new StarType(24,3,[150,50,50]),
        		"M" => new StarType(84,2,[196,0,0]),
        		"WhiteDwarf" => new StarType(94,1,[200,200,200]),
        		"RedGiant" => new StarType(99,7,[225,0,0]),
        		"Supergiant" => new StarType(100,10,[255,0,0]),
            ];
            foreach ($YoungStarChances as $name => $type) {
            if ($starChoice <= $type->Density) {
                $star->Radius = $type->Radius;
                $star->Color = ColorNoise($type->Color);
            	break;
            }
        	}
            
        }
    }
	function getSpiralStarChances($star) {
        if ($star->Age < 8) {
        global $k;
        $YoungStarChances = [
                "O" => new StarType(1, 5, [0,228,228]),
            	"B" => new StarType(2, 5, [0,0,255]),
        		"A" => new StarType(3, 4, [128,50,50]),
        		"F" => new StarType(6, 3, [128,128,50]),
        		"G" => new StarType(13,3,[196,196,0]),
        		"K" => new StarType(24,3,[150,50,50]),
        		"M" => new StarType(64,2,[196,0,0]),
        		"WhiteDwarf" => new StarType(94,1,[200,200,200]),
        		"RedGiant" => new StarType(99,7,[225,0,0]),
        		"Supergiant" => new StarType(100,10,[255,0,0]),
      ];
      $starChoice = random_int(0,100);
      foreach ($YoungStarChances as $name => $type) {
            if ($starChoice <= $type->Density) {
                $star->Radius = $type->Radius;
                $star->Color = ColorNoise($type->Color);
            	break;
            }
        	}
        }
        else {
            $OldStarChances = [
                "WhiteDwarf" => new StarType(80,7,[255,255,255]),
            	"RedGiant" => new StarType(97,7,[225,0,0]),
            	"Supergiant" => new StarType(100,10,[255,0,0]),
            ];
            $starChoice = random_int(0,100);
            foreach ($OldStarChances as $name => $type) {
            if ($starChoice <= $type->Density) {
                $star->Radius = $type->Radius;
                
                if ($star->Age > 30 && random_int(0, $star->Age) >= 10) {
                	$dark = [0, 0, 0];
                    $star->Color = $dark;
                }
                else {
                    $star->Color = $type->Color;
                	}
                
            	}
                break;
        	}
        }
    }
	function populateStarType($star) {
        
        if ($star->Region == "Bulge") {
            getBulgeStarType($star);
        }
        else if ($star->Region == "Middle") {
            getMiddleStarType($star);
        }
        else {
            getSpiralStarChances($star);
        }
        
        
        
    }
	function distanceFromOrigin($pt) {
        // sqrt((x2-x1)^2+(y2-y1)^2);
        return sqrt($pt[0]*$pt[0] + $pt[1]*$pt[1]);
    }
	function generateSpiralStarPosition() {
        global $thetaEnd, $r0, $k, $arm_thickness,$armsn,$data, $galaxyAge;
        // irl stars are more densely populated near the galaxy center (0,0), and near the spirals.
        // so here I can allocate a zone (say d=1.5), where stars are most likely to appear
        // and as it gets farther from the zone, it'll become less and less likely for stars to appear
        $generation_method = mt_rand(0, 1000);
        $toReturn = new Star([0,0], [0, 0, 0], 1, "N/A");
        if ($generation_method < ($data != null ? $data["spiralDensity"]*10 : 500) ) {
        // spiral arm have more stars
        $theta = random_float(0, $thetaEnd);
        $arm_choice = random_int(0,$armsn-1);
        $phase = 2 * M_PI * $arm_choice / $armsn;
        $r_spiral = ($r0)*exp($k*$theta);
        $noise = gaussian(0, $arm_thickness);
        $r = $r_spiral + $noise;
        
        $x = $r*cos($theta+$phase);
        $y = $r*sin($theta+$phase);
        $toReturn->Position = [$x, $y];
        
        $distanceA = distanceFromOrigin($toReturn->Position);
        $distanceB = log10($distanceA*$theta);
        $agedNoise = $galaxyAge+gaussian(0,1.1);
        $toReturn->Age = clamp( $agedNoise-$distanceB, 0.1, $galaxyAge-1.1);
        $toReturn->Region = "Spiral";
        $toReturn->Theta = $theta;
        }
        else if ($generation_method < ($data != null ? 10*($data["bulgeDensity"]+$data["spiralDensity"]) : 900) ) {
            $r = sampleDiskRadius($r0*0.25);
            $theta = random_float(0,2*M_PI);
            
            $x = random_float(0,$r0*0.25)*cos($theta);
            $y = random_float(0,$r0*0.25)*sin($theta);
            
        	$toReturn->Position = [$x, $y];
            
            $ageNoise = gaussian(0,0.75);
        	$toReturn->Age = clamp(($galaxyAge)+$ageNoise, $galaxyAge-1.25, $galaxyAge );
        	$toReturn->Region = "Bulge";
            
            
        }
        else if ($generation_method < ($data != null ? ($data["bulgeDensity"]+$data["middleDensity"]+$data["spiralDensity"])*10 : 1000 )) {
            $bulge_radius = $r0*1.25;
            $toReturn->Position = [$bulge_radius*gaussian(0,0.3),$bulge_radius*gaussian(0,0.3)];
            
            $ageNoise = gaussian(0,0.9);
        	$toReturn->Age = clamp(($galaxyAge)+$ageNoise, $galaxyAge-1.5, $galaxyAge-0.5 );
        	$toReturn->Region = "Middle";
        }
        else {
            $toReturn->Position = [mt_rand(-1000,1000), mt_rand(-1000,1000)];
        }
        
        populateStarType($toReturn);
        return $toReturn;
        
    }
	function generateStars() {
        global $galaxyType, $staramt;
        $toreturn = [];
        for ($i = 0; $i < $staramt; $i++) {
            switch ($galaxyType) {
                case "spiral": {
            		$toreturn[] = generateSpiralStarPosition();
                }
            }
        }
        return $toreturn;
    }
	$starlist = json_encode(generateStars());
	echo $starlist;
	
	




?>