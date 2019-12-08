{if (!empty($msg_wrong_shop_context))}<div id="msg_wrong_shop_context">{$msg_wrong_shop_context|replace:['[',']']:['<strong>', '</strong>']}</div>{/if}
<form action="{$link->getAdminLink($controller_name)|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" class="form-horizontal">
    <div class="panel">
        <div class="panel-heading"><i class="rzicon-configuration"></i> {l s='General' d='Admin.Global'}</div>
        <div class="form-wrapper">
            <div class="form-group">
                {assign var='helpBox' value={l s='If you are already registered and have received our key, please enter it in this field.[br][plink]Not registered yet? Get it now![/plink]' mod='relevanz'}}
                {assign var='helpBox' value={$helpBox|replace:['[br]','[plink]','[/plink]']:['<br>','<a style="text-decoration: underline;" href="https://releva.nz" target="_blank">','</a>']}}
                <label class="control-label col-lg-3">
                    {l s='Your releva.nz API Key' mod='relevanz'}
                    <span class="help-box" data-toggle="popover" data-html="true" data-content="{$helpBox|replace:'"':'&quot;'}"></span>
                </label>
                <p class="sr-only">{$helpBox|unescape:"html" nofilter}</p>
                <div class="col-lg-9">
                    <input type="text" id="conf_apikey" name="relevanz[conf][apikey]" class="form-control" value="{$credentials->getApiKey()}">
                </div>
            </div>
            {if ($credentials->isComplete())}
                <div class="form-group row">
                    <label class="control-label col-lg-3">{l s='Your Customer ID' mod='relevanz'}</label>
                    <div class="col-lg-9">
                        <input type="text" id="conf_customer_id" class="form-control" value="{$credentials->getUserId()}" readonly>
                    </div>
                </div>
                <div class="form-group row">
                    {assign var='helpBox' value={l s='Please submit this URL to the releva.nz customer service.' mod='relevanz'}}
                    <label class="control-label col-lg-3">
                        {l s='Export-URL' mod='relevanz'}
                        <span class="help-box" data-toggle="popover" data-html="true" data-content="{$helpBox|replace:'"':'&quot;'}"></span>
                    </label>
                    <p class="sr-only">{$helpBox|unescape:"html" nofilter}</p>
                    <div class="col-lg-9">
                        <input type="text" id="conf_export_url" class="form-control" value="{$exportUrl}" readonly>
                    </div>
                </div>
            {/if}
        </div>

        <div class="panel-footer">
            <button type="submit" class="btn btn-default pull-right" name="submitOptionswebservice_account"><i class="process-icon-save"></i> Speichern</button>
        </div>
    </div>
</form>

{if (!empty($debug))}
	<pre>{$debug}</pre>
{/if}