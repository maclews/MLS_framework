<?php
// ALL OUTPUT IS STORED IN $result
$data = Gstatic::app()->db()->query('SELECT * FROM settings');
$result = $data;  /// FOR EXPLANATION PURPOSES