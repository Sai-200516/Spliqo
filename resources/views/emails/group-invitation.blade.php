<x-mail::message>
# You have been invited to join **{{ $group->name }}**

**{{ $inviterName }}** has invited you to join the group **{{ $group->name }}** on Spliqo - a collaborative expense management tool.

Click the button below to accept the invitation. The link expires in 7 days.

<x-mail::button :url="$acceptUrl" color="success">
Accept invitation
</x-mail::button>

If you were not expecting this invitation, you can safely ignore this email.

Thanks,
The Spliqo team
</x-mail::message>