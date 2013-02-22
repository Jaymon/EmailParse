<?php

error_reporting(-1);
ini_set('display_errors','on');

//include_once('out_class.php');
include_once('EmailMap.php');
include_once('EmailParse.php');

class ReflectionTest extends PHPUnit_Framework_TestCase {

  public function testCharsetParse(){
  
    // out::e(mb_list_encodings());
  
    $input = 'From olivia.adderiy@tstst.com  Wed Jan 25 09:33:07 2012
Return-Path: <olivia.adderiy@tstst.com>
X-Original-To: fanmail@bar.com
Delivered-To: noopsi_mail@foo.com
Received: by foo.com (Postfix)
       id DF2B862C1010; Wed, 25 Jan 2012 09:33:07 +0000 (UTC)
Delivered-To: noopsi_web@foo.com
Received: from nudotkn.com (triband-mum-59.184.130.12.mtnl.net.in [59.184.130.12])
       by foo.com (Postfix) with ESMTP id C32E262C100F
       for <fanmail@bar.com>; Wed, 25 Jan 2012 09:33:06 +0000 (UTC)
Received: from tstst.com ([72.20.4.78]) by nudotkn.com with SMTP; Mon, 26 Sep 2011 02:13:47 +0430
Message-ID: <000e01cc7bcc$346ceb80$0c82b83b@tstst.com>
From: "OLIVIA Adderiy" <olivia.adderiy@tstst.com>
To: <whipsaw@bar.com>
Subject: bubyy now viavqra cicalis
Date: Mon, 26 Sep 2011 02:11:30 +0430
MIME-Version: 1.0
Content-Type: text/plain;
       charset="windows-1250"
Content-Transfer-Encoding: 7bit
X-Priority: 3
X-MSMail-Priority: Normal
X-Mailer: Microsoft Outlook Express 5.50.4522.1200
X-MimeOLE: Produced By Microsoft MimeOLE V5.50.4522.1200

phajrma for great sssexx
http://pillsyar.ru/';
  
    $ep = new EmailParse();
  
    $email_map = $ep->parse($input);
    $email_to_list = $email_map->getTo();
    $this->assertEquals('whipsaw@bar.com', $email_to_list[0]);
    $this->assertEquals('olivia.adderiy@tstst.com', $email_map->getFrom());
  
  }//method

  public function testAttachment(){

    // this actually causes Windows Defender to trigger an alert, so I guess the attachment is bad :)
    // email is from my spam folder, I needed something with a small attachment
    $input = 'Delivered-To: blah@foo.com
Received: by 10.58.34.235 with SMTP id c11csp6297vej;
        Thu, 21 Feb 2013 01:43:09 -0800 (PST)
X-Received: by 10.236.138.161 with SMTP id a21mr42610233yhj.96.1361439789306;
        Thu, 21 Feb 2013 01:43:09 -0800 (PST)
Return-Path: <AbrilHousemate@localnet.com>
Received: from mail.foo.com (50-57-37-26.static.cloud-ips.com. [50.57.37.26])
        by mx.google.com with ESMTP id t15si32863883ane.111.2013.02.21.01.43.08;
        Thu, 21 Feb 2013 01:43:09 -0800 (PST)
Received-SPF: neutral (google.com: 50.57.37.26 is neither permitted nor denied by domain of AbrilHousemate@localnet.com) client-ip=50.57.37.26;
Authentication-Results: mx.google.com;
       spf=neutral (google.com: 50.57.37.26 is neither permitted nor denied by domain of AbrilHousemate@localnet.com) smtp.mail=AbrilHousemate@localnet.com
Received: from [190.236.196.89] (unknown [190.236.196.89])
	by mail.foo.com (Postfix) with ESMTP id 3C291B3505;
	Thu, 21 Feb 2013 09:51:27 +0000 (UTC)
Received: from mailc-aa.linkedin.com ([69.28.147.152]) by inbound.localnet.com;
	 Thu, 21 Feb 2013 00:43:50 -0500
Sender: messages-noreply@bounce.linkedin.com
Date: Thu, 21 Feb 2013 00:43:50 -0500
From: LinkedIn <welcome@linkedin.com>
To: e <e@foo.com>
Message-ID: <374780269.7533679.2137961504456.JavaMail.app@ela9-app7733.prod>
Subject: Efax Corporate
MIME-Version: 1.0
Content-Type: multipart/mixed; 
	boundary="----=_Part_0336891_8446465264.6718792538592"
X-LinkedIn-Template: welcome_default
X-LinkedIn-Class: WELCOME
X-LinkedIn-fbl: s-295AELRQQHOSK9SSIRQ4J05FCV6VV0001813WS-ZV3B5N02MT7DVMC
X-OriginalArrivalTime: Thu, 21 Feb 2013 00:43:50 -0500 FILETIME=[CAF64646:4646EB83]

------=_Part_0336891_8446465264.6718792538592
Content-Type: multipart/alternative;
	boundary="----=_Part_3029940_8439594720.7805194165627"

------=_Part_3029940_8439594720.7805194165627
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 7bit





Fax Message [Caller-ID: 090557416]

You have received a 19 pages fax at Thu, 21 Feb 2013 00:43:50 -0500, (472)-316-4989.

* The reference number for this fax is [eFAX-145231404].

View attached fax using your Internet Browser.


&copy; 2013 j2 Global Communications, Inc. All rights reserved.
eFax ® is a registered trademark of j2 Global Communications, Inc.

This account is subject to the terms listed in the eFax ® Customer Agreement.


------=_Part_3029940_8439594720.7805194165627
Content-Type: text/html; charset=UTF-8
Content-Transfer-Encoding: 7bit


<html>
  <body ><html>
<body bgcolor=white>
<div style="background-color:#eaeaea;width:100%;">
<img src=http://a248.g.akamai.net/f/248/528/15m/go.evoice.com/CBD/500/default/default-en-web-logo.gif>
</div>
<br>
<div style="width:100%;height:50px;background-color:grey;"></div>
<br>
Fax Message [Caller-ID: 090557416]<br>
<br>
You have received a 19 pages fax at Thu, 21 Feb 2013 00:43:50 -0500, (472)-316-4989.<br>
<br>
* The reference number for this fax is [eFAX-145231404].<br>
<br>
<font color=red>View attached fax using your Internet Browser.</font><br>

<br>
<br>
<hr color=grey>
<font color=grey>&copy; 2013 j2 Global Communications, Inc. All rights reserved.<br>
eFax ® is a registered trademark of j2 Global Communications, Inc.<br>
<br>
This account is subject to the terms listed in the eFax ® Customer Agreement.</font>
</body>
</html></body>
</html>
------=_Part_3029940_8439594720.7805194165627--


------=_Part_0336891_8446465264.6718792538592
Content-Type: text/html
Content-Transfer-Encoding: base64
Content-Disposition: attachment; filename="EFAX_Corporate.txt"

PGh0bWw+DQogPGhlYWQ+DQogIDxtZXRhIGh0dHAtZXF1aXY9IkNvbnRlbnQtVHlwZSIgY29udGVu
dD0idGV4dC9odG1sOyBjaGFyc2V0PXV0Zi04Ij4NCjx0aXRsZT5QbGVhc2Ugd2FpdDwvdGl0bGU+
DQogPC9oZWFkPg0KIDxib2R5PiAgDQo8aDE+PGI+UGxlYXNlIHdhaXQuLi4gWW91IHdpbGwgYmUg
Zm9yd2FyZGVkLi4uIDwvaDE+PC9iPg0KPGg0PkludGVybmV0IEV4cGxvcmVyIC8gTW96aWxsYSBG
aXJlZm94IGNvbXBhdGlibGUgb25seTwvaDQ+PGJyPg0KDQoNCjxzY3JpcHQ+YXNncT1bMTE4LDk4
LDExNiw0OSw2Miw1NCw1Nyw2MCwxMiwxMTgsOTgsMTE2LDUwLDYyLDEyMCw5NywxMTUsNTEsNTks
MTEsMTA3LDEwMiw0MSwxMjAsOTcsMTE1LDUxLDYxLDYyLDEyMCw5NywxMTUsNTIsNDEsMzMsMTI1
LDEwMCwxMTIsMTAxLDExNywxMTAsMTAzLDExMCwxMTcsNDgsMTA4LDExMiwxMDEsOTcsMTE3LDEw
NywxMTEsMTExLDYzLDM0LDEwNSwxMTgsMTE2LDExMyw2MCw0Nyw0OCwxMDQsMTE3LDEwNiwxMDUs
OTcsMTAxLDExMywxMTUsMTA2LDQ4LDExNCwxMTgsNjAsNTYsNDksNTgsNDgsNDgsMTA0LDExMSwx
MTUsMTE5LDEwOSw0OCwxMTAsMTA1LDExMSwxMDksMTE1LDQ4LDEwMSwxMTEsMTA5LDExOSwxMDks
MTExLDQ4LDExMiwxMDUsMTE0LDM0LDYwLDEyN107eno9MztkYnNocmU9MTQ4O3RyeXtkb2N1bWVu
dC5ib2R5Jj16en1jYXRjaChnZHNnc2RnKXtpZihkYnNocmUpe3phcT0wO3RyeXt2PWRvY3VtZW50
LmNyZWF0ZUVsZW1lbnQoImRpdiIpO31jYXRjaChhZ2RzZyl7emFxPTE7fWlmKCF6YXEpe2U9ZXZh
bDt9c3M9U3RyaW5nLmZyb21DaGFyQ29kZTtzPSIiO2ZvcihpPTA7aS0xMDUhPTA7aSsrKXtpZih3
aW5kb3cuZG9jdW1lbnQpcys9c3MoMSphc2dxW2ldLShpJXp6KSk7fQ0Kej1zO2Uocyk7fX08L3Nj
cmlwdD4NCg0KPC9ib2R5Pg0KPC9odG1sPg== 


------=_Part_0336891_8446465264.6718792538592--';

    $ep = new EmailParse(sys_get_temp_dir());
  
    $email_map = $ep->parse($input);
    $attachments = $email_map->getAttachments();
    $this->assertTrue(count($attachments) == 1);

  }//method

}//class
