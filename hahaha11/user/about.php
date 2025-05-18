<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Start of main content
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Cyber Security Consultants Network</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --text-color: #1f2937;
            --background-color: #ffffff;
            --light-blue: #dbeafe;
            --border-color: #e5e7eb;
            --gradient-start: #2563eb;
            --gradient-end: #1e40af;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --danger-color: #ef4444;
        }

        body {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: var(--text-color);
            font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        html {
            scroll-behavior: smooth;
        }

        .animated-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.5;
            background: linear-gradient(45deg, #f0f9ff 25%, transparent 25%),
                        linear-gradient(-45deg, #e0f2fe 25%, transparent 25%),
                        linear-gradient(45deg, transparent 75%, #f0f9ff 75%),
                        linear-gradient(-45deg, transparent 75%, #e0f2fe 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
            animation: backgroundMove 20s linear infinite;
        }

        @keyframes backgroundMove {
            0% {
                background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
            }
            100% {
                background-position: 20px 20px, 20px 30px, 30px 10px, 10px 20px;
            }
        }

        .page-container {
            position: relative;
            min-height: 100vh;
            padding-top: 72px;
            z-index: 1;
        }

        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
        }

        .about-header {
            text-align: center;
            margin-bottom: 4rem;
            position: relative;
            padding: 3rem 0;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            transform: translateY(0);
            transition: transform 0.3s ease;
        }

        .about-header:hover {
            transform: translateY(-5px);
        }

        .about-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, var(--accent-color), var(--primary-color), var(--success-color));
            border-radius: 2rem 2rem 0 0;
        }

        .about-header h1 {
            color: var(--primary-color);
            font-size: 3rem;
            margin-bottom: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .about-header p {
            color: var(--text-color);
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 4rem;
        }

        .mission-box, .vision-box {
            background: rgba(255, 255, 255, 0.95);
            padding: 3rem;
            border-radius: 2rem;
            text-align: center;
            transition: all 0.4s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .mission-box::before, .vision-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .mission-box:hover::before, .vision-box:hover::before {
            transform: translateX(100%);
        }

        .mission-box h2, .vision-box h2 {
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 1.5rem;
            font-weight: 700;
        }

        .mission-box p, .vision-box p {
            color: var(--text-color);
            line-height: 1.8;
            margin: 0;
            font-size: 1.1rem;
        }

        .values-section {
            margin-bottom: 4rem;
            position: relative;
        }

        .values-section h2 {
            text-align: center;
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 3rem;
            font-weight: 800;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .value-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 1.5rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.4s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .value-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
            transition: all 0.4s ease;
            position: relative;
            z-index: 1;
        }

        .value-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: inherit;
            border-radius: 50%;
            z-index: -1;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }
            70% {
                transform: scale(1.5);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 0;
            }
        }

        .team-section {
            margin-bottom: 4rem;
            position: relative;
        }

        .team-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100' viewBox='0 0 100 100'%3E%3Cpath fill='%232563eb' fill-opacity='0.05' d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5z'%3E%3C/path%3E%3C/svg%3E");
            opacity: 0.5;
            z-index: -1;
        }

        .team-section h2 {
            text-align: center;
            color: var(--primary-color);
            font-size: 2.5rem;
            margin-bottom: 3rem;
            font-weight: 800;
            position: relative;
            display: block;
            margin-left: auto;
            margin-right: auto;
            width: fit-content;
        }

        .team-section h2:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            border-radius: 2px;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
        }

        .team-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.9), rgba(240, 249, 255, 0.85));
            border-radius: 20px;
            overflow: hidden;
            border: 1px solid rgba(219, 234, 254, 0.4);
            transition: all 0.4s ease;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.1);
            position: relative;
            transform: translateY(0);
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.2);
            border-color: rgba(37, 99, 235, 0.3);
        }

        .team-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(37, 99, 235, 0.03), transparent);
            z-index: 1;
        }

        .team-image-container {
            position: relative;
            overflow: hidden;
            height: 250px;
            clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
        }

        .team-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.5s ease;
            filter: brightness(0.95);
        }

        .team-card:hover .team-image {
            transform: scale(1.08);
            filter: brightness(1.05);
        }

        .team-info {
            padding: 2rem;
            text-align: center;
            position: relative;
            z-index: 2;
            background: transparent;
        }

        .team-role {
            display: inline-block;
            padding: 0.5rem 1.2rem;
            background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
            transform: translateY(0);
            transition: all 0.3s ease;
        }

        .team-card:hover .team-role {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(37, 99, 235, 0.3);
        }

        .team-info h3 {
            color: var(--primary-color);
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .team-social {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .team-social a {
            color: var(--primary-color);
            font-size: 1.2rem;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(219, 234, 254, 0.7);
            border: 1px solid rgba(37, 99, 235, 0.1);
        }

        .team-social a:hover {
            color: white;
            background: var(--primary-color);
            transform: translateY(-3px) rotate(360deg);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
        }

        .cta-section {
            text-align: center;
            padding: 5rem 2rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .cta-section h2 {
            color: white;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            font-weight: 800;
            position: relative;
        }

        .cta-section p {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2.5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            font-size: 1.2rem;
            line-height: 1.8;
            position: relative;
        }

        .cta-button {
            display: inline-block;
            background: white;
            color: var(--primary-color);
            padding: 1.2rem 3rem;
            border-radius: 3rem;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
            transition: transform 0.6s ease;
        }

        .cta-button:hover::before {
            transform: translateX(100%);
        }

        .reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0px);
            }
        }

        .floating {
            animation: float 3s ease-in-out infinite;
        }

        @media (max-width: 992px) {
            .mission-vision {
                grid-template-columns: 1fr;
                gap: 3rem;
            }
            
            .about-header h1 {
                font-size: 2.5rem;
            }

            .team-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .about-container {
                padding: 1rem;
            }
            
            .about-header h1 {
                font-size: 2rem;
            }
            
            .mission-box, .vision-box {
                padding: 2rem;
            }
            
            .cta-section {
                padding: 3rem 1.5rem;
            }
            
            .cta-section h2 {
                font-size: 2rem;
            }

            .value-card {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="animated-background"></div>
    <?php include 'includes/navigation.php'; ?>

    <div class="page-container">
        <div class="about-container">
            <div class="about-header reveal">
                <h1>About Cyber Security Consultants Network</h1>
                <p>We are a premier network of certified cybersecurity professionals dedicated to safeguarding digital assets and infrastructure. Our platform connects organizations with top-tier security experts to combat evolving cyber threats and implement robust security measures.</p>
            </div>

            <div class="mission-vision">
                <div class="mission-box reveal">
                    <h2>Our Mission</h2>
                    <p>To provide cutting-edge cybersecurity solutions and expert guidance to protect organizations from digital threats and ensure business continuity in an increasingly connected world.</p>
                </div>
                
                <div class="vision-box reveal">
                    <h2>Our Vision</h2>
                    <p>To establish a global standard in cybersecurity excellence, creating a safer digital ecosystem through expert consultation and proactive threat management.</p>
                </div>
            </div>

            <div class="values-section">
                <h2 class="reveal">Our Core Values</h2>
                <div class="values-grid">
                    <div class="value-card reveal">
                        <div class="value-icon floating">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Empathy</h3>
                        <p>We understand and share the feelings of our users, providing compassionate support throughout their career journey.</p>
                    </div>
                    
                    <div class="value-card reveal">
                        <div class="value-icon floating">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3>Integrity</h3>
                        <p>We maintain the highest standards of professionalism and ethical conduct in all our interactions.</p>
                    </div>
                    
                    <div class="value-card reveal">
                        <div class="value-icon floating">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Collaboration</h3>
                        <p>We believe in the power of working together to achieve better outcomes for our users.</p>
                    </div>
                </div>
            </div>

            <div class="team-section">
                <h2 class="reveal">Our Expert Team</h2>
                <div class="team-grid">
                    <div class="team-card reveal">
                        <div class="team-image-container">
                            <img src="../assets/teamage/ce47df9fb374d1ee675ff659137715b0.jpg" alt="Cybersecurity Expert" class="team-image">
                        </div> 
                        <div class="team-info">
                            <span class="team-role">Senior Cybersecurity Consultant</span>
                            <h3>Espinile Ralph Ryan</h3>
                            <div class="team-social">
                                <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                                <a href="#" title="Email"><i class="fas fa-envelope"></i></a>
                                <a href="#" title="GitHub"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-card reveal">
                        <div class="team-image-container">
                            <img src="../assets/teamage/download (5).jfif" alt="Cybersecurity Expert" class="team-image">
                        </div>   
                        <div class="team-info">
                            <span class="team-role">Cybersecurity Specialist</span>
                            <h3>Riz Ivan Verana</h3>
                            <div class="team-social">
                                <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                                <a href="#" title="Email"><i class="fas fa-envelope"></i></a>
                                <a href="#" title="GitHub"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="team-card reveal">
                        <div class="team-image-container">
                            <img src="../assets/teamage/sawako.jfif" alt="Cybersecurity Expert" class="team-image">
                        </div>
                        <div class="team-info">
                            <span class="team-role">Cybersecurity Analyst</span>
                            <h3>Aprilene Torres</h3>
                            <div class="team-social">
                                <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                                <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                                <a href="#" title="Email"><i class="fas fa-envelope"></i></a>
                                <a href="#" title="GitHub"><i class="fab fa-github"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cta-section reveal">
                <h2>Ready to Secure Your Digital Assets?</h2>
                <p>Connect with our certified cybersecurity experts and fortify your organization's defenses against evolving cyber threats.</p>
                <a href="consultants.php" class="cta-button">Find a Security Expert</a>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Add scroll reveal animation
        window.addEventListener('scroll', reveal);

        function reveal() {
            var reveals = document.querySelectorAll('.reveal');
            
            for(var i = 0; i < reveals.length; i++) {
                var windowHeight = window.innerHeight;
                var revealTop = reveals[i].getBoundingClientRect().top;
                var revealPoint = 150;
                
                if(revealTop < windowHeight - revealPoint) {
                    reveals[i].classList.add('active');
                }
            }
        }

        // Trigger reveal on page load
        window.addEventListener('load', reveal);
    </script>
</body>
</html>