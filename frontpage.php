<?php
http_response_code(503);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Unavailable</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f6f9;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .container {
            text-align: center;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            max-width: 500px;
        }

        h1 {
            color: #e74c3c;
            margin-bottom: 10px;
        }

        h2 {
            margin: 0;
            font-size: 20px;
            color: #555;
        }

        p {
            margin-top: 15px;
            line-height: 1.5;
            color: #666;
        }

        .status {
            margin-top: 20px;
            padding: 10px;
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            border-radius: 5px;
        }

        .footer {
            margin-top: 25px;
            font-size: 12px;
            color: #aaa;
        }

        .btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.2s;
        }

        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>503</h1>
        <h2>Webpage Unavailable</h2>

        <p>
            The hosting service for this website is currently out of order.
            Please try again later.
        </p>

        <div class="status">
            ⚠ Subscription Expired — Hosting services temporarily suspended.
        </div>

        <p>
            To restore access, please renew your hosting subscription or contact your service provider.
        </p>

        <a href="#" class="btn">Renew Subscription</a>

        <div class="footer">
            Error Code: HOSTING_503_TIMEOUT<br>
            Server Status: Offline
        </div>
    </div>
</body>
</html>