@php($name = isset($data['name']) ? $data['name'] : 'User')
@php($sms_code = isset($data['mail_code']) ? $data['mail_code'] : 'undefined')
<p> Dear {{$name}}, </p>
<p>You sms code is: {{$sms_code}}</p>