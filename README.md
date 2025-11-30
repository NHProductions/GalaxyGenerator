# GalaxyGenerator
Generates realistic looking galaxies in PHP.


## How does it work?
So, the frontend basically sends a bunch of data to stars.php, which is the actual algorithm.
For the spirals, I used a logarithmic spiral (r = r<sub>0</sub>e<sup>kθ</sup>)
