<?php

echo "Web server is working!";
echo "<br>Current time: " . date('Y-m-d H:i:s');
echo "<br>Request URI: " . $_SERVER['REQUEST_URI'];
echo "<br>Request method: " . $_SERVER['REQUEST_METHOD'];
