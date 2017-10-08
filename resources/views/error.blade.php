<h3>{{ get_class($exception) }}</h3>
<b>Message:</b> <?php echo $exception->getMessage() ?>

{{ str_repeat('=', 130) }}

<h3>Stack trace:</h3>
<pre><?php echo $exception->getTraceAsString() ?>
</pre>