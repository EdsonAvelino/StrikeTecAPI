<h3>{{ get_class($exception) }}</h3>
<b>Message:</b> <?php echo $exception->getMessage()."\n" ?>

<?php echo 'in '. basename($exception->getFile()) .' (line '. $exception->getLine().')'; ?>

<h3>Stack trace:</h3>
<pre><?php echo $exception->getTraceAsString(); ?></pre>