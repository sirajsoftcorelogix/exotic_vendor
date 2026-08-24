<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://developers.eraahi.com/eInvoiceGateway/eicore/v1.03/Invoice',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
    "Data" : "tWZ5SbX5+AoPuIJ6JaYqL7h+kOzqwf0sE9B50lCuU4kT3yuS92uezyopysQDjk3hVUsT0x2vALadfG250j0J/HBiBHpemT36+doX3sbT4T/1 Zh1AqEH5WhVMf0Rhw/HvpIeiEAVd0Dlsn4MNV89cDuvQ4RMBB1dGvWmS2VdrIG+vQ8s9W5eE3XAD1eL+BJSxZfbFkvRM5xuOXr3OqpDpbSLDfnOZyreTuy8Y/JRSlocbC42lVli/6 q5m/Ix6Igd/By3QEidehiskojojSiGZ85jVmgDDcoiSADS1Eks6bTyKhLTVoAKxLSCXn3WYBNCGX4Nyv0/ZhE87swgLEwo69mscBbOAmunfgxsGW2er6xuuPASW/07 Ag3aXWtLZbSUXf/j2BaUZ62DcUKIAg86Tp2IlreOraAJn2vaLlXFtLgMca1FgZ9eeSQBY1fN7rsnO/wVVTMt2691AG5fJQ8eer/lmCPFu6SQZfxF8wt3pSY0BzQPYqd5VgWG7XOR8pNXdwxf5UIZHZsa7MP0mLbnMBcD/z/9 fRg4TftBQZPLFhubnVbCztEy6dQSJUQLmC4bBwlo7CFQz33L6f8jf+iMzqXb1LRqHjXWHzfjYEZGNOn5rvn6HFar0wrTTPLavh+2 VWcm8NMeYViJ4riocmaC6I6tkaALphpNg89wIGaXThRrBjGujpzYGpy9vJBsYCNOBfCfL0JDib+s0YwdT6FtKHa6PmXWor4wKhQoQlKjLupzprqyx/AIeuHA9/QaWVE8KFyuWIpcvgQeuwobICdjA6JOqtHMQ1MZQXnYBMibL93GqTxB788gSxpSWTub/Rmx1T8EP8l4XOQZVlXQp8haP0N6ODjcY2b+M230s23E56AJoQFzrEkKwRe+blZkbBiqLZQM1ME+Iqn8ZWeXTvUnOLJDWzS21hj0twzEvyAUTZzUCBARtAm8MNBmj5FkzFo0A/D7SmND/BfeOzllLa9Cnu6fXocJt60bXR4mSQ5XL0aiaOmoN06IwUeL+RiNekyB3+IJQHCvhI2XMPN7BgVFWISQDuTCHsGF/I/vIMqTW0JaJj98dpyYLAUMdHU7dgLyOgHUeXx4MlA+pLp3EvGa1LlS82yaE2YA+Y1Gk1FQSGSY0G4xNK1piftDgBO8Ul6IUbhVHQ43olSfjl8gDdboEwHVxknC/X7PTYHhDLctToI7NoE97BbequBRcEjUhgcxvi2JlMMqypgiZqH/o/5 QQ1p6Ccx96PvSzADYvBRedpPjtrgfiSEp9npoxyiiy47fpmNxv6TGvS45L8BMoJRUNs8S+F9pOLfwhCoPnqmd0W5ZUpgg9gW6dvLk4ZTWzkqN4CMNt5zD6IH3GDK5rJvENsT9NyUaSG3DesrbSMxgvIPI="
}',
  CURLOPT_HTTPHEADER => array(
    'Ocp-Apim-Subscription-Key: AL6x9c9S1b7g8h9S7C',
    'Gstin: 07AGAPA5363L002',
    'user_name: AL001',
    'AuthToken: 1ZxwoZy4XNZeS82snReXSIY9h',
    'Content-Type: application/json',
    'Cookie: sess_map=fqcuxerztqqzbryduezaywetarayrduvcaebxuzfaubacufxccubxurxbdttrwqvrxbzcfrszstsquwezbeswaueqvbtzzxsueufyzdsqyacfefubucaqeqaeduuvyuaydbvbrsryxqubruvydafdrsxveqecbdcdyaxvawuuwaayadq'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
