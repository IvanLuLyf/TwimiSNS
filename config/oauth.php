<?php
return $_ENV['BUNNY_OAUTH'] ? json_decode($_ENV['BUNNY_OAUTH'], true) : [];