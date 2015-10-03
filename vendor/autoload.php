<?php

namespace proj4php;

spl_autoload_register(function($class) {
		if (stripos($class, __NAMESPACE__) === 0)
		{
			@include(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'src' . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen(__NAMESPACE__))) . '.php');
		}
	}
);
