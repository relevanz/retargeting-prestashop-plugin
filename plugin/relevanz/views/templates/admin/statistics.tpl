<article id="relevanz-stats" class="{if (!empty($msg_wrong_shop_context))}has_msg_wrong_shop_context{/if}">
    {if (!empty($msg_wrong_shop_context))}<div id="msg_wrong_shop_context">{$msg_wrong_shop_context|replace:['[',']']:['<strong>', '</strong>']}</div>{/if}
    <iframe src="{$stats_url}" class="loading"></iframe>
</article>