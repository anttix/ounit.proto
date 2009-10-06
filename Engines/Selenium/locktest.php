<?php

$fp = fopen("../../tmp/selenium.lock", "w+");

if (flock($fp, LOCK_EX)) { // do an exclusive lock
    sleep(60);
    flock($fp, LOCK_UN); // release the lock
} else {
    echo "Couldn't lock the file !";
}

fclose($fp);

?>
