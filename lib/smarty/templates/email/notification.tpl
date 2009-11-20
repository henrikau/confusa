Hi,

Using your {$subscriber} account you have ordered a {$product_name|default:"TCS
eScience Personal"} certificate through {$confusa_url}.

Your new {$product_name|default:"TCS eScience Personal"} certificate with
subject {$dn} is ready.
Please proceed to the download page and follow the instructions:

   {$download_url}

For support please contact your local helpdesk{if isset($subscriber_support_email) || isset($subscriber_support_url)} at:
   {$subscriber_support_email}
   {$subscriber_support_url}
{else}.{/if}

When you contact your local helpdesk please be sure to include the order
details you find below in your support question.

THAT WASN'T YOU?
================
If you did NOT order this certificate then it is likely your {$subscriber}
account has been abused and you need to notify your local helpdesk immediately.


ORDER DETAILS
=============
Order number: {$order_number}
Issue date	: {$issue_date}
subject		: {$dn}
Request date: {$issue_date}
IP address of requesting machine: {$ip_address}


Best regards,
{$nren} eScience Personal Certificate team
