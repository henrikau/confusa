<h3>about::confusa</h3>
<div class="spacer"></div>

{if $person->inAdminMode()}
{include file='admin/about_confusa.tpl'}
{/if}


{* credit to other GPL'd software *}
<p class="info">{$l10n_infotext_thanks1}</p>

<h4>{$l10n_heading_authentication}</h4>

<p class="info">
  <a href="http://rnd.feide.no/simplesamlphp" target="_blank">simplesamlphp</a> {$l10n_infotext_thanks2}
</p>

<h4>{$l10n_heading_icons}</h4>
<p class="info">
  {$l10n_infotext_thanks3}
</p>

{* please include your very own tailored HTML-credit-file here, if you
   are not operating the portal from the university of Tilburg *}

<h4>{$l10n_heading_software}</h4>

<p class="info">{$l10n_infotext_thanks4}</p>
<ul class="info">
  <li>PHP</li>
  <li>Smarty templating engine</li>
  <li>MySQL</li>
  <li>curl</li>
</ul>

{* add your own operational credits here, if you are a portal instance operator *}
{if isset($op_creds)}
<h4>{$l10n_heading_operations}</h4>
<p>{$l10n_infotext_thanks5}</p>
{$op_creds}
{/if}
