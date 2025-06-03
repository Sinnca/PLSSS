<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultant Login - Career Guidance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --text-color: #1e293b;
            --light-text: #64748b;
            --background: #f8fafc;
            --white: #ffffff;
            --error-bg: #fee2e2;
            --error-text: #b91c1c;
            --success-color: #10b981;
            --border-color: #e2e8f0;
            --input-bg: #f1f5f9;
            --input-focus: #e0f2fe;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: var(--text-color);
            min-height: 100vh;
            margin: 0;
        }
        
        .page-container {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            padding-top: 72px;
        }
        
        .wave-top {
            height: 150px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            border-radius: 0 0 50% 50% / 0 0 100px 100px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .wave-top::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            animation: wave 8s linear infinite;
        }
        
        .wave-bottom {
            height: 150px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            border-radius: 50% 50% 0 0 / 100px 100px 0 0;
            width: 100%;
            margin-top: auto;
            position: relative;
            overflow: hidden;
        }
        
        .wave-bottom::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            animation: wave 8s linear infinite;
        }
        
        @keyframes wave {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .content-area {
            display: flex;
            justify-content: center;
            flex: 1;
            padding: 20px;
            margin-top: -50px;
            margin-bottom: -50px;
            z-index: 10;
        }
        
        .form-container {
            background-color: var(--white);
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            margin: auto 0;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeInUp 0.6s ease-out;
        }
        
        .form-container:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            animation: gradientFlow 3s ease infinite;
        }
        
        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            margin-bottom: 30px;
            position: relative;
            animation: fadeInUp 0.8s ease-out;
        }
        
        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        h1:hover::after {
            width: 100px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
            animation: fadeInUp 0.8s ease-out;
            animation-fill-mode: both;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: var(--input-bg);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
            background-color: var(--input-focus);
            transform: translateY(-2px);
        }
        
        .form-group input:focus + label {
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .btn {
            display: block;
            width: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: var(--white);
            padding: 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
            animation: fadeInUp 0.8s ease-out;
            animation-delay: 0.3s;
            animation-fill-mode: both;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }
        
        .btn:hover::after {
            transform: translateX(100%);
        }
        
        .error-container {
            background-color: var(--error-bg);
            color: var(--error-text);
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 8px;
            border-left: 4px solid var(--error-text);
            animation: shake 0.5s ease-in-out;
            box-shadow: 0 2px 10px rgba(185, 28, 28, 0.1);
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .error-list {
            margin: 0;
            padding-left: 20px;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: var(--light-text);
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            animation: fadeInUp 0.8s ease-out;
            animation-delay: 0.4s;
            animation-fill-mode: both;
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .register-link a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 1px;
            background-color: var(--primary-color);
            transition: width 0.3s ease;
        }
        
        .register-link a:hover::after {
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .form-container {
                padding: 30px 20px;
                margin: 20px;
                border-radius: 12px;
            }
            
            h1 {
                font-size: 2rem;
            }
            
            .wave-top,
            .wave-bottom {
                height: 100px;
            }
            
            .form-group input {
                padding: 10px 12px;
            }
            
            .btn {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="wave-top"></div>
        
        <div class="content-area">
            <div class="form-container">
                <h1>Consultant Login</h1>
                
                <?php if(isset($errors) && !empty($errors)): ?>
                    <div class="error-container">
                        <ul class="error-list">
                            <?php foreach($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="../actions/process_login.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    </div>
                    
                    <input type="hidden" name="user_type" value="consultant">
                    <div class="form-group">
                        <button type="submit" class="btn">Sign In</button>
                    </div>
                </form>
                
                <div class="register-link">
                    New consultant? <a href="register.php">Create an account</a>
                </div>
            </div>
        </div>
        
        <div class="wave-bottom"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>