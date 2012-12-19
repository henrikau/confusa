    <h2>{$l10n_header_choosecountry}</h2>
    <br />

    <div id="map" style="width: 684px; height: 600px"></div>


    {literal}
    <script type="text/javascript">
    var configuredCountries = {
	{/literal}
	{if isset($configuredCountries)}
	{foreach from=$configuredCountries item=country}
	"{$country}": 1,
	{/foreach}
	{/if}
	{literal}
    }

function showInfo(event, code) {
    if (configuredCountries[code]) {
	var idplist = {
	    {/literal}
	    {if isset($idplist)}
	    {foreach from=$idplist key=key item=item}
	    {$key} : {literal}[{/literal}
		{foreach from=$item item=url}
			       "{$url}",
		{/foreach}
	       {literal}],{/literal}
	    {/foreach}

	    {/if}
	    {literal}
	}
	var form= document.createElement("form");
	form.setAttribute("method", "post");
	form.setAttribute("action", "{/literal}{$disco_path}{literal}");

	var hfc = document.createElement("input");
	hfc.setAttribute("type", "hidden");
	hfc.setAttribute("name", "country");
	hfc.setAttribute("value", code);
	form.appendChild(hfc);
	for (var i=0;i<idplist[code].length;i++)
	{
	    var hf = document.createElement("input");
	    hf.setAttribute("type", "hidden");
	    hf.setAttribute("name", "IDPList[]");
	    hf.setAttribute("value", idplist[code][i]);
	    form.appendChild(hf);
	}
	document.body.appendChild(form);
	form.submit();
    }
}

$(function(){
    $('#map').vectorMap({
	map: {/literal}'{$idp_map_name}'{literal},
	backgroundColor: '#FFF',

	series: {
	    regions: [{
		values: configuredCountries,
		scale: ['#006600'],
	    }]
	},
	onRegionClick: showInfo,
	regionStyle: {
        initial: {
          fill: 'GRAY'
        },
      }
    });
});

</script>
    {/literal}
