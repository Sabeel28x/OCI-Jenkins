<!DOCTYPE html>
<html>
<head>
    <title>Welcome to My Apache Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
            background-color: #00bfff;
        }
        h1 {
            color: #333;
        }
        p {
            color: #666;
        }
        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #999;
        }
    </style>
</head>
<body>
    <h1>Welcome to My Apache Application !!!</h1>
    <p>This is a sample application hosted on Apache.</p>
    <p>Server Private IP: <strong><?php echo $_SERVER['SERVER_ADDR']; ?></strong></p>
    <div class="footer">Powered by Apache Web Server</div>
</body>
</html>
