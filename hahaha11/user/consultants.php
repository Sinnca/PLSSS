<?php
require_once '../config/db.php';
require_once '../includes/header.php';
// Get search parameters
$specialty = isset($_GET['specialty']) ? $_GET['specialty'] : '';
$name = isset($_GET['name']) ? $_GET['name'] : '';
// Build query
$sql = "SELECT c.*, u.name FROM consultants c
        JOIN users u ON c.user_id = u.id
        WHERE 1=1";
if (!empty($specialty)) {
    $specialty = mysqli_real_escape_string($conn, $specialty);
    $sql .= " AND c.specialty LIKE '%$specialty%'";
}
if (!empty($name)) {
    $name = mysqli_real_escape_string($conn, $name);
    $sql .= " AND u.name LIKE '%$name%'";
}
$consultants = mysqli_query($conn, $sql);
// Get all specialties for filter dropdown
$sql_specialties = "SELECT DISTINCT specialty FROM consultants";
$specialties = mysqli_query($conn, $sql_specialties);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultants - Career Guidance System</title>
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
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            min-height: 100vh;
        }

        .page-container {
            position: relative;
            min-height: 100vh;
            padding-top: 72px;
        }

        /* Enhanced Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.85), rgba(29, 78, 216, 0.85)), url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            color: white;
            padding: 120px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
            min-height: 450px;
            display: flex;
            align-items: center;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            width: 100%;
        }

        .hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 1s ease;
        }

        .hero-content p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 1s ease 0.2s;
            animation-fill-mode: both;
        }

        /* Enhanced Search Card */
        .search-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin: -50px auto 40px;
            max-width: 1000px;
            box-shadow: var(--card-shadow);
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease 0.4s;
            animation-fill-mode: both;
        }

        .search-section-modern {
            background: #fff;
            border-radius: 22px;
            box-shadow: 0 4px 32px rgba(37,99,235,0.10);
            padding: 40px 32px 32px 32px;
            margin: -50px auto 40px auto;
            max-width: 1000px;
            display: flex;
            flex-direction: column;
            gap: 28px;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease 0.4s;
            animation-fill-mode: both;
        }
        .search-section-modern .search-title {
            margin: 0 0 18px 0;
            font-size: 2rem;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
            letter-spacing: 0.5px;
        }
        .search-form-modern {
            display: flex;
            gap: 18px;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-form-modern input[type="text"],
        .search-form-modern select {
            padding: 14px 18px;
            border-radius: 12px;
            border: 1.5px solid #e0e7ef;
            background: #f8fafc;
            font-size: 1.08rem;
            color: #1e293b;
            transition: border 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(37,99,235,0.04);
            outline: none;
        }
        .search-form-modern input[type="text"]:focus,
        .search-form-modern select:focus {
            border: 1.5px solid #2563eb;
            box-shadow: 0 4px 16px rgba(37,99,235,0.10);
        }
        .search-form-modern button[type="submit"] {
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(90deg, #2563eb 60%, #3b82f6 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 14px 38px;
            font-size: 1.08rem;
            font-weight: 700;
            box-shadow: 0 2px 12px rgba(37,99,235,0.10);
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
        }
        .search-form-modern button[type="submit"]:hover {
            background: linear-gradient(90deg, #1d4ed8 60%, #2563eb 100%);
            box-shadow: 0 6px 20px rgba(37,99,235,0.15);
            transform: translateY(-2px) scale(1.04);
        }
        @media (max-width: 800px) {
            .search-section-modern {
                padding: 24px 8px 18px 8px;
            }
            .search-form-modern {
                flex-direction: column;
                gap: 14px;
            }
        }

        /* Enhanced Consultant Cards */
        .consultant-card {
            background: linear-gradient(135deg, #f8faff 70%, #e6eeff 100%);
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(37,99,235,0.10), 0 2px 8px rgba(37,99,235,0.06);
            padding: 0;
            margin-bottom: 36px;
            transition: box-shadow 0.3s, transform 0.25s, border 0.2s;
            border: 1.5px solid #e0e7ef;
            position: relative;
            overflow: hidden;
        }
        .consultant-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(120deg, rgba(37,99,235,0.04) 0%, rgba(59,130,246,0.03) 100%);
            z-index: 0;
            pointer-events: none;
        }
        .consultant-card:hover {
            box-shadow: 0 20px 48px rgba(37,99,235,0.16), 0 6px 24px rgba(37,99,235,0.13);
            border: 1.5px solid #2563eb;
            transform: translateY(-6px) scale(1.025);
        }
        .consultant-card > * {
            position: relative;
            z-index: 1;
        }

        .consultant-card:hover {
            box-shadow: 0 16px 48px rgba(37,99,235,0.16);
            transform: translateY(-4px) scale(1.02);
        }

        .consultant-card-header-modern {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 24px 24px 0 24px;
        }

        .consultant-avatar-modern {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2563eb 60%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #fff;
            font-weight: 700;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(37,99,235,0.10);
        }
        .consultant-avatar-modern img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .consultant-name-modern {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .consultant-specialty-modern {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: 16px;
            padding: 4px 16px;
            margin-top: 2px;
            margin-bottom: 2px;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
        }

        .consultant-bio-modern {
            padding: 18px 24px 0 24px;
            color: #64748b;
            font-size: 1rem;
            min-height: 40px;
        }

        .consultant-card-footer-modern {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px 24px 24px;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
            border-radius: 0 0 18px 18px;
        }

        .consultant-rating-modern {
            color: #ffc107;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 2px;
        }

        .btn-modern {
            background: #fff;
            color: #2563eb;
            border: 2px solid #2563eb;
            border-radius: 12px;
            padding: 8px 28px;
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(37,99,235,0.04);
            text-decoration: none;
            outline: none;
        }
        .btn-modern:hover, .btn-modern:focus {
            background: #2563eb;
            color: #fff;
            box-shadow: 0 4px 16px rgba(37,99,235,0.12);
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

        /* Enhanced Buttons */
        .btn {
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: var(--gradient-primary);
            border: none;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-outline {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            background: transparent;
        }

        .btn-outline:hover {
            background: var(--gradient-primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Enhanced Form Elements */
        .form-group input, .form-group select {
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 16px;
            transition: var(--transition);
            background: #f8fafc;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
            background: white;
        }

        /* Enhanced Results Summary */
        .results-summary {
            text-align: center;
            margin: 40px 0;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
        }

        .results-summary h2 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-color);
            margin: 0;
        }

        /* Enhanced No Results */
        .no-results {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
        }

        .no-results-icon {
            font-size: 80px;
            color: var(--primary-color);
            margin-bottom: 30px;
            opacity: 0.5;
        }

        .no-results h3 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--text-color);
        }

        .no-results p {
            font-size: 16px;
            color: var(--light-text);
            max-width: 500px;
            margin: 0 auto;
        }

        .consultant-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            font-weight: bold;
            margin-right: 15px;
            overflow: hidden; /* Ensure the image doesn't overflow */
        }

        .consultant-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ensures the image covers the entire area */
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Navigation Styles */
        .navbar {
            background-color: var(--primary-color) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 12px 0;
        }

        .navbar-brand, .nav-link {
            color: var(--white) !important;
            font-weight: 500;
        }

        .nav-link:hover {
            color: rgba(255, 255, 255, 0.85) !important;
        }

        .navbar .nav-link.active {
            border-bottom: 2px solid white;
        }

        .dropdown-menu {
            border-radius: 10px;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            border: none;
            padding: 8px;
        }

        .dropdown-item {
            border-radius: 6px;
            padding: 8px 16px;
        }

        .dropdown-item:hover {
            background-color: #f0f7ff;
            color: var(--primary-color);
        }

        /* Main Content */
        .main-content {
            padding-bottom: 80px;
        }

        /* Search Form */
        .search-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 40px;
            margin-top: 15px;
            box-shadow: var(--card-shadow);
        }

        .search-title {
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 24px;
            color: var(--text-color);
            font-weight: 600;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .search-btn-group {
            display: flex;
            align-items: flex-end;
            min-width: 120px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }

        .search-btn {
            padding: 12px 24px;
            width: 100%;
        }

        .search-btn i {
            margin-right: 8px;
        }

        /* Results Summary */
        .results-summary {
            margin-bottom: 30px;
        }

        .results-summary h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-color);
        }

        /* Consultants Grid */
        .consultants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .consultant-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }

        .consultant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .consultant-card-header {
            padding: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .consultant-info {
            flex-grow: 1;
        }

        .consultant-bio {
            color: var(--text-light);
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 15px;
            line-height: 1.6;
        }

        .consultant-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            font-size: 14px;
            color: var(--text-light);
        }

        .meta-item i {
            margin-right: 8px;
            color: var(--primary-color);
        }

        .consultant-card-footer {
            padding: 15px 20px;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .consultant-rating {
            color: #ffc107;
            display: flex;
            align-items: center;
        }

        .consultant-rating i {
            margin-right: 3px;
        }

        .reviews-count {
            color: var(--text-light);
            font-size: 14px;
            margin-left: 5px;
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .consultants-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .navbar-collapse {
                background-color: var(--primary-color);
                padding: 1rem;
                border-radius: 10px;
                margin-top: 1rem;
            }
            
            .navbar-nav {
                margin-bottom: 1rem;
            }
            
            .navbar-nav.ms-auto {
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .hero-section {
                padding: 60px 0;
            }
            
            .hero-content h1 {
                font-size: 32px;
            }
            
            .consultants-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .search-form {
                flex-direction: column;
            }
            
            .form-group {
                width: 100%;
            }
            
            .hero-section {
                padding: 40px 0;
            }
            
            .hero-content h1 {
                font-size: 28px;
            }
        }

        .text-red {
            color: #ff0000;
        }

        /* Footer Styles */
        .footer {
            background: var(--text-color);
            color: var(--white);
            padding: 60px 0 20px;
            margin-top: 80px;
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
            margin-bottom: 20px;
            position: relative;
        }

        .footer-section h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -10px;
            width: 50px;
            height: 2px;
            background: var(--primary-color);
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
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 35px;
            color: var(--white);
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .social-links a:hover {
            background: var(--primary-color);
            transform: translateY(-3px);
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
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-section h3::after {
                left: 50%;
                transform: translateX(-50%);
            }

            .social-links {
                justify-content: center;
            }
        }

        .tag {
            display: inline-block;
            background: rgba(37,99,235,0.08);
            color: #2563eb;
            font-size: 12px;
            margin-right: 4px;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="page-container">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container">
                <div class="hero-content">
                    <h1>Find Expert <span class="text-red">Consultants</span></h1>
                    <p>Connect with top professionals in various fields to help grow your business</p>
                </div>
            </div>
        </section>

        <div class="container main-content">
            <!-- Search Form -->
            <div class="search-card">
                <h2 class="search-title">Search Consultants</h2>
                <form method="GET" action="" class="search-form">
                    <div class="form-group">
                        <label for="name">Consultant Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" placeholder="Search by name">
                    </div>
                    <div class="form-group">
                        <label for="specialty">Specialty</label>
                        <select id="specialty" name="specialty">
                            <option value="">All Specialties</option>
                            <option value="Network Security" <?php echo ($specialty == 'Network Security') ? 'selected' : ''; ?>>Network Security</option>
                            <option value="Application Security" <?php echo ($specialty == 'Application Security') ? 'selected' : ''; ?>>Application Security</option>
                            <option value="Cloud Security" <?php echo ($specialty == 'Cloud Security') ? 'selected' : ''; ?>>Cloud Security</option>
                            <option value="Security Operations" <?php echo ($specialty == 'Security Operations') ? 'selected' : ''; ?>>Security Operations</option>
                            <option value="Risk Management" <?php echo ($specialty == 'Risk Management') ? 'selected' : ''; ?>>Risk Management</option>
                            <option value="Incident Response" <?php echo ($specialty == 'Incident Response') ? 'selected' : ''; ?>>Incident Response</option>
                        </select>
                    </div>
                    <div class="form-group search-btn-group">
                        <button type="submit" class="btn btn-primary search-btn">
                            <i class="fa-solid fa-magnifying-glass"></i> Search
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Results Count -->
            <?php 
            $count = mysqli_num_rows($consultants);
            $filterText = '';
            if (!empty($specialty) && !empty($name)) {
                $filterText = " for \"" . htmlspecialchars($name) . "\" in " . htmlspecialchars($specialty);
            } elseif (!empty($specialty)) {
                $filterText = " in " . htmlspecialchars($specialty);
            } elseif (!empty($name)) {
                $filterText = " for \"" . htmlspecialchars($name) . "\"";
            }
            ?>
            <div class="results-summary">
                <h2><?php echo $count; ?> Consultant<?php echo ($count != 1) ? 's' : ''; ?> found<?php echo $filterText; ?></h2>
            </div>
            
            <!-- Consultants Grid -->
            <div class="consultants-grid">
            <?php if ($count > 0) : ?>
                <?php while ($consultant = mysqli_fetch_assoc($consultants)) : ?>
                    <div class="consultant-card">
                        <div class="consultant-card-header-modern">
                            <div class="consultant-avatar-modern">
                                <?php 
                                if (!empty($consultant['profile_photo'])) {
                                    echo '<img src="../' . htmlspecialchars($consultant['profile_photo']) . '" alt="' . htmlspecialchars($consultant['name']) . '">';
                                } else {
                                    $initials = strtoupper(substr($consultant['name'], 0, 1));
                                    echo "<span>$initials</span>";
                                }
                                ?>
                            </div>
                            <div>
                                <div class="consultant-name-modern"><?php echo htmlspecialchars($consultant['name']); ?></div>
                                <span class="consultant-specialty-modern"><?php echo htmlspecialchars($consultant['specialty']); ?></span>
                            </div>
                        </div>
                        <div class="consultant-bio-modern">
                            <?php 
                            if (isset($consultant['bio'])) {
                                echo substr(htmlspecialchars($consultant['bio']), 0, 60) . '...';
                            } else {
                                echo "Professional consultant specializing in " . htmlspecialchars($consultant['specialty']);
                            }
                            ?>
                        </div>
                        <div class="consultant-card-footer-modern">
                            <div class="consultant-rating-modern">
                                <?php 
                                $rating = isset($consultant['rating']) ? $consultant['rating'] : 0;
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $rating) {
                                        echo '<i class="fa-solid fa-star"></i>';
                                    } else {
                                        echo '<i class="fa-regular fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <a href="consultant_appointment.php?id=<?php echo $consultant['id']; ?>" class="btn-modern">View Profile</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else : ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fa-solid fa-face-frown"></i>
                    </div>
                    <h3>No consultants found</h3>
                    <p>No consultants match your search criteria. Please try different search terms.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Us</h3>
                    <p>We connect businesses with expert consultants to help them achieve their goals and drive growth through professional guidance and strategic solutions.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-section links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="consultants.php">Consultants</a></li>
                        <li><a href="services.php">Services</a></li>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
