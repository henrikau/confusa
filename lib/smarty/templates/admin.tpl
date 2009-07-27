{if $person->in_admin_mode()}
{if $person->is_nren_admin() ||  $person->is_subscriber_admin()}
<H3>Subscriber and NREN administration</H3>
Add, edit or remove subscribers <BR />
<BR />
[ {$link_urls.subscribers} ]
[ {$link_urls.accounts} ]
[ {$link_urls.nren} ]

{/if}
{/if}