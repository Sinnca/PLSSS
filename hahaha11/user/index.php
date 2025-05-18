<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Fetch featured consultants by highest feedback, only those who have updated their info
$sql = "SELECT c.*, u.name, 
            IFNULL(AVG(f.rating), 0) AS avg_rating, 
            COUNT(f.id) AS feedback_count
        FROM consultants c
        JOIN users u ON c.user_id = u.id
        LEFT JOIN appointment_feedback f ON f.consultant_id = c.id
        WHERE 
            (c.experience IS NOT NULL AND c.experience != '') OR
            (c.location IS NOT NULL AND c.location != '') OR
            (c.qualification IS NOT NULL AND c.qualification != '') OR
            (c.description IS NOT NULL AND c.description != '')
        GROUP BY c.id
        ORDER BY avg_rating DESC, feedback_count DESC, c.is_featured DESC
        LIMIT 3";
$featured_consultants = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Guidance System</title>
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
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --gradient-primary: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        }

        /* Add smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        .page-container {
            position: relative;
            min-height: 100vh;
            padding-top: 72px;
        }

        /* Enhanced Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.85), rgba(29, 78, 216, 0.85)), url('https://images.unsplash.com/photo-1552664730-d307ca884978?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 150px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            animation: fadeInUp 1s ease-out;
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .hero-content p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Featured Consultants Section */
        .featured-consultants {
            padding: 80px 0;
            background: var(--white);
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-color);
            margin-bottom: 3rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-light));
            border-radius: 2px;
        }

        .consultants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        /* Enhanced Consultant Cards */
        .consultant-card {
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .consultant-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--hover-shadow);
        }

        .consultant-img {
            width: 100%;
            height: 280px;
            overflow: hidden;
            position: relative;
        }

        .consultant-img::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50%;
            background: linear-gradient(to top, rgba(0,0,0,0.5), transparent);
        }

        .consultant-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .consultant-card:hover .consultant-img img {
            transform: scale(1.1);
        }

        .no-image {
            width: 100%;
            height: 100%;
            background: var(--background);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: var(--light-text);
        }

        .consultant-info {
            padding: 1.5rem;
        }

        .consultant-info h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .consultant-specialty {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .consultant-details {
            margin-bottom: 1rem;
            color: var(--light-text);
            font-size: 0.9rem;
        }

        .consultant-details i {
            width: 20px;
            color: var(--primary-color);
        }

        .consultant-description {
            color: var(--light-text);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .consultant-rating {
            margin-bottom: 1.5rem;
        }

        .consultant-rating i {
            color: #f59e0b;
            margin-right: 2px;
        }

        /* Services Section */
        .services-section {
            padding: 80px 0;
            background: var(--background);
        }
        
        .cybersecurity-header {
            max-width: 800px;
            margin: 0 auto 40px;
            text-align: center;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            padding: 30px;
            border: 3px solid #3b82f6;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(37, 99, 235, 0.2);
        }
        
        .cybersecurity-header h2 {
            color: #ffffff;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }
        
        .cybersecurity-header h2:after {
            content: '';
            position: absolute;
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, #ffffff, #c7d2fe);
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }
        
        .cybersecurity-header .section-description {
            color: #ffffff;
            font-size: 1.2rem;
            margin-top: 20px;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        /* Enhanced Service Cards */
        .service-card {
            background: var(--white);
            padding: 2.5rem;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--card-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.4s ease;
            z-index: 0;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--hover-shadow);
        }

        .service-card:hover::before {
            opacity: 0.05;
        }

        .service-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            color: white;
            transition: transform 0.4s ease;
            position: relative;
            z-index: 1;
        }

        .service-card:hover .service-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .service-card h3 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .service-card p {
            color: var(--light-text);
            line-height: 1.6;
        }

        .services-cta {
            text-align: center;
            margin-top: 3rem;
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
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

        .cta-content {
            position: relative;
            z-index: 1;
        }

        .cta-content h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }

        .cta-content p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Enhanced Buttons */
        .btn {
            display: inline-block;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(-100%);
            transition: transform 0.4s ease;
        }

        .btn:hover::before {
            transform: translateX(0);
        }

        .btn-primary {
            background: linear-gradient(90deg, #4361ee, #2563eb);
            border: none;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(67,97,238,0.10);
            transition: background 0.2s;
        }

        .btn-primary:hover {
            background: #3a56d4;
        }

        .btn-light {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid white;
        }

        .btn-light:hover {
            background: white;
            color: var(--primary-color);
            transform: translateY(-3px);
        }

        /* Animations */
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

        /* Enhanced Footer */
        .footer {
            background: var(--text-color);
            color: var(--white);
            padding: 80px 0 20px;
            margin-top: 80px;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-section h3 {
            color: var(--white);
            font-size: 1.5rem;
            margin-bottom: 25px;
            position: relative;
            display: inline-block;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 50px;
            height: 3px;
            background: var(--gradient-primary);
            transition: width 0.3s ease;
        }

        .footer-section:hover h3::after {
            width: 100%;
        }

        .footer-section p {
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .social-links {
            margin-top: 20px;
        }

        .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            color: var(--white);
            margin-right: 10px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .social-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            transform: scale(0);
            transition: transform 0.4s ease;
            border-radius: 50%;
        }

        .social-links a:hover::before {
            transform: scale(1);
        }

        .social-links a i {
            position: relative;
            z-index: 1;
        }

        .social-links a:hover {
            transform: translateY(-5px);
        }

        .footer-section.links ul {
            list-style: none;
            padding: 0;
        }

        .footer-section.links ul li {
            margin-bottom: 10px;
        }

        .footer-section.links ul li a {
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer-section.links ul li a:hover {
            color: var(--primary-color);
            padding-left: 5px;
        }

        .footer-section.contact p {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .footer-section.contact p i {
            margin-right: 10px;
            color: var(--primary-color);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .footer-bottom p {
            color: #94a3b8;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content p {
                font-size: 1.2rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .consultants-grid,
            .services-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .service-card {
                padding: 2rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }

            .footer-section h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
        }

        /* Loading Animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--white);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        .loading.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--background);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Blue border for main card */
        .card {
            border: 2px solid #4361ee;
            box-shadow: 0 4px 24px rgba(67,97,238,0.08);
        }

        /* Section headers */
        .card-title {
            color: #2563eb;
            border-left: 4px solid #2563eb;
            padding-left: 0.7rem;
            font-weight: 700;
        }

        /* Calendar day highlight */
        .calendar-day.selected, .calendar-day.has-availability {
            background: #2563eb !important;
            color: #fff !important;
            border-radius: 8px;
        }

        /* Input focus */
        input:focus, select:focus, textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 2px #c7d2fe;
        }

        /* Alert styling */
        .alert {
            border-radius: 10px;
            border-left: 5px solid #2563eb;
            background: #eef2ff;
            color: #1e293b;
        }
    </style>
</head>
<body>
    <!-- Add Loading Animation -->
    <div class="loading">
        <div class="loading-spinner"></div>
    </div>

    <?php include 'includes/navigation.php'; ?>

    <div class="page-container">
<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1>Find Expert <span style="color: red;">Cybersecurity Consultants</span> For Your Security Needs</h1>
            <p>Connect with industry professionals who can help protect your digital assets and infrastructure</p>
            <a href="#featured-consultants" class="btn btn-primary">Find Consultants</a>
        </div>
    </div>
</section>

<!-- Featured Consultants Section -->
<section id="featured-consultants" class="featured-consultants">
    <div class="container">
        <h2 class="section-title">Featured Consultants</h2>
        <div class="consultants-grid">
            <?php if(mysqli_num_rows($featured_consultants) > 0): ?>
                <?php while($consultant = mysqli_fetch_assoc($featured_consultants)): ?>
                    <div class="consultant-card">
                        <div class="consultant-img">
                            <?php if(!empty($consultant['profile_photo'])): ?>
                                <img src="../<?php echo htmlspecialchars($consultant['profile_photo']); ?>" alt="<?php echo htmlspecialchars($consultant['name']); ?>">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fa-solid fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="consultant-info">
                            <h3><?php echo $consultant['name']; ?></h3>
                            <p class="consultant-specialty"><?php echo $consultant['specialty']; ?></p>
                            <div class="consultant-details">
                                <p><i class="fa-solid fa-briefcase"></i> <?php echo isset($consultant['experience']) ? $consultant['experience'] . ' years experience' : 'Experience not specified'; ?></p>
                                <p><i class="fa-solid fa-location-dot"></i> <?php echo isset($consultant['location']) ? $consultant['location'] : 'Location not specified'; ?></p>
                                <p><i class="fa-solid fa-graduation-cap"></i> <?php echo isset($consultant['qualification']) ? $consultant['qualification'] : 'Qualification not specified'; ?></p>
                            </div>
                            <p class="consultant-description">
                                <?php echo isset($consultant['description']) ? $consultant['description'] : 'No description available for this consultant.'; ?>
                            </p>
                            <div class="consultant-rating">
                                <?php 
                                $avg_rating = isset($consultant['avg_rating']) ? round($consultant['avg_rating'], 1) : 0;
                                for($i = 1; $i <= 5; $i++): 
                                ?>
                                    <?php if($i <= round($avg_rating)): ?>
                                        <i class="fa-solid fa-star"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <span style="margin-left:0.5rem; color:#2563eb; font-weight:600;">
                                    <?php echo $avg_rating; ?> / 5.0
                                    (<?php echo $consultant['feedback_count']; ?>)
                                </span>
                            </div>
                                    <a href="#" class="btn btn-primary view-profile-btn" data-id="<?php echo $consultant['id']; ?>">View Profile</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results" style="text-align: center; width: 100%; padding: 2rem;">
                    <i class="fa-solid fa-info-circle mb-3" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                    <p style="font-size: 1.2rem; color: var(--light-text);">No featured consultants available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services-section">
    <div class="container">
        <div class="cybersecurity-header">
            <h2>Our Cybersecurity Services</h2>
            <p class="section-description">Expert cybersecurity solutions for your organization</p>
        </div>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3>Network Security</h3>
                <p>Comprehensive protection for your network infrastructure and data</p>
            </div>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fa-solid fa-code"></i>
                </div>
                <h3>Application Security</h3>
                <p>Secure your applications from vulnerabilities and threats</p>
            </div>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fa-solid fa-cloud"></i>
                </div>
                <h3>Cloud Security</h3>
                <p>Protect your cloud infrastructure and data with expert solutions</p>
            </div>
        </div>
        <div style="text-align:center; margin-top:2rem;">
            <a href="services.php" class="btn btn-primary btn-lg">Explore Services</a>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to enhance your security posture?</h2>
            <p>Schedule a free security assessment with one of our cybersecurity experts today</p>
            <a href="contact.php" class="btn btn-light">Get Started</a>
        </div>
    </div>
</section>
    </div>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h3>Career Guidance System</h3>
                    <p>Connecting businesses with expert IT consultants to drive digital transformation and success.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-section links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="services.php">Services</a></li>
                        <li><a href="consultants.php">Consultants</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section contact">
                    <h3>Contact Info</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Business Street, City, Country</p>
                    <p><i class="fas fa-phone"></i> +1 234 567 8900</p>
                    <p><i class="fas fa-envelope"></i> info@careerguidance.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Career Guidance System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Consultant Profile Modal -->
    <div class="modal fade" id="consultantProfileModal" tabindex="-1" aria-labelledby="consultantProfileModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="consultantProfileModalLabel">Consultant Profile</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="consultantProfileModalBody">
            <!-- Profile content will be loaded here -->
            <div class="text-center">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Loading Animation
        window.addEventListener('load', function() {
            const loading = document.querySelector('.loading');
            loading.classList.add('hidden');
        });

        // Smooth Scroll for Navigation Links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Animate elements on scroll
        const observerOptions = {
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.consultant-card, .service-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.view-profile-btn').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var consultantId = this.getAttribute('data-id');
                    var modalBody = document.getElementById('consultantProfileModalBody');
                    modalBody.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                    var modal = new bootstrap.Modal(document.getElementById('consultantProfileModal'));
                    modal.show();

                    // Fetch profile via AJAX
                    fetch('consultant_profile_modal.php?id=' + consultantId)
                        .then(response => response.text())
                        .then(html => {
                            modalBody.innerHTML = html;
                        })
                        .catch(() => {
                            modalBody.innerHTML = '<div class="alert alert-danger">Failed to load profile.</div>';
                        });
                });
            });
        });
    </script>
</body>
</html>