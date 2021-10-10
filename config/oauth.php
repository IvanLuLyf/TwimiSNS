<?php
return isset($_ENV['BUNNY_OAUTH']) ? json_decode($_ENV['BUNNY_OAUTH'], true) : [];