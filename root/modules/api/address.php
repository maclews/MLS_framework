<?php
if (isset($_GET['city']) && is_numeric($_GET['city'])) {
  $result = Gstatic::app()->db()->query('SELECT `id`, `ulica_nazwa` FROM `ulica` WHERE `miasto_id` = :city', [':city' => $_GET['city']]);
} else {
  if (isset($_GET['showerror'])) echo 'ERROR: CITY undefined';
}
if (isset($_GET['street']) && is_numeric($_GET['street'])) {
  $result = Gstatic::app()->db()->query('SELECT `kod_pocztowy`.`kod_pocztowy_wartosc` FROM `ulica` JOIN `kod_pocztowy` ON `ulica`.`kod_pocztowy_id` = `kod_pocztowy`.`id` WHERE `ulica`.`id` =:street', [':street' => $_GET['street']]);
} else {
  if (isset($_GET['showerror'])) echo 'ERROR: STREET undefined';
}
