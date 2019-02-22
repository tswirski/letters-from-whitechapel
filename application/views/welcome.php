<?=View::factory('template/page-welcome');?>
<?php if ($activated === true): ?>
    <script>
        $.popupManager.alert('Account activated! Welcome to Whitechapel.');
    </script>
<?php endif; ?>

<script>
    $(document).ready(function(){
        pageWelcome.init();
    });
</script>