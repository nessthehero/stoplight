<?php

  file_put_contents(__DIR__ . '/test.txt', 'foo bar baz');
  if (file_exists(__DIR__ . '/test.txt')) {
    echo 'File created successfully';
  } else {
    echo 'Failed to create file';
  }
