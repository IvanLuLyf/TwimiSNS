<?php
return isset($_ENV['BUNNY_EMAIL']) ? json_decode($_ENV['BUNNY_EMAIL'], true) : [];