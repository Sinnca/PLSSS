<?php
require_once '../config/db.php';
require_once '../includes/header.php';

// Fetch all unique specialties/services
$sql = "SELECT DISTINCT specialty FROM consultants ORDER BY specialty";
$specialties_result = mysqli_query($conn, $sql);

// Count consultants for each specialty
$services = [];
while ($specialty = mysqli_fetch_assoc($specialties_result)) {
    $specialty_name = $specialty['specialty'];

    // Get count of consultants with this specialty
    $count_sql = "SELECT COUNT(*) as consultant_count FROM consultants WHERE specialty = '" . mysqli_real_escape_string($conn, $specialty_name) . "'";
    $count_result = mysqli_query($conn, $count_sql);
    $count_data = mysqli_fetch_assoc($count_result);

    // Get one featured consultant for this specialty (if available)
    $featured_sql = "SELECT c.id, c.bio, c.hourly_rate, u.name
                        FROM consultants c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.specialty = '" . mysqli_real_escape_string($conn, $specialty_name) . "'
                        AND c.is_featured = 1
                        LIMIT 1";
    $featured_result = mysqli_query($conn, $featured_sql);
    $featured_consultant = mysqli_fetch_assoc($featured_result);

    // Store service data
    $services[] = [
        'name' => $specialty_name,
        'consultant_count' => $count_data['consultant_count'],
        'featured_consultant' => $featured_consultant
    ];
}

// Filter services by search keyword if present
$search_keyword = isset($_GET['specialty']) ? trim($_GET['specialty']) : '';
$filtered_services = $services;
if ($search_keyword !== '') {
    $filtered_services = array_filter($services, function($service) use ($search_keyword) {
        return stripos($service['name'], $search_keyword) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Career Guidance System</title>
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

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--background);
        color: var(--text-color);
        line-height: 1.6;
    }

    .hero-section {
        background: linear-gradient(135deg, rgba(37, 99, 235, 0.85), rgba(29, 78, 216, 0.85)), url('https://images.unsplash.com/photo-1552664730-d307ca884978?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        color: white;
        padding: 120px 0 80px 0;
        text-align: center;
        position: relative;
        overflow: hidden;
        border-radius: 0 0 32px 32px;
        box-shadow: 0 8px 24px rgba(74, 144, 226, 0.10);
    }

    .hero-content {
        position: relative;
        z-index: 1;
        max-width: 700px;
        margin: 0 auto;
        padding: 0 20px;
        animation: fadeInUp 1s ease-out;
    }

    .hero-content h1 {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 1.2rem;
        letter-spacing: 1.5px;
        text-shadow: 2px 2px 8px rgba(53, 122, 189, 0.10);
    }

    .hero-content p {
        font-size: 1.25rem;
        opacity: 0.92;
        margin-bottom: 2rem;
        font-weight: 400;
        max-width: 700px;
        margin-left: auto;
        margin-right: auto;
    }

    .page-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        min-height: 100vh;
        padding-top: 20px;
        padding-bottom: 20px;
        position: relative;
        overflow: hidden;
    }

    .container {
        width: 100%;
        max-width: 1200px;
    }

    .main-heading {
        text-align: center;
        margin: 2rem 0;
        position: relative;
        padding: 3rem;
        background: var(--gradient-primary);
        border-radius: 20px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        transform: translateY(0);
        transition: all 0.3s ease;
        overflow: hidden;
        animation: fadeInUp 1s ease;
    }

    .main-heading h1 {
        font-size: 3.8rem;
        font-weight: 800;
        color: white;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 4px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        position: relative;
        display: inline-block;
    }

    .main-heading h1::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: #fff;
        border-radius: 2px;
    }

    .intro-text {
        text-align: center;
        margin: 2rem auto;
        color: var(--text-color);
        font-size: 1.2rem;
        line-height: 1.6;
        max-width: 800px;
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border-left: 5px solid var(--primary-color);
        position: relative;
        animation: fadeInUp 1s ease 0.2s;
        animation-fill-mode: both;
    }

    .intro-text::before {
        content: '"';
        position: absolute;
        top: -20px;
        left: 20px;
        font-size: 4rem;
        color: var(--primary-color);
        opacity: 0.2;
        font-family: serif;
    }

    .search-box {
        display: flex;
        justify-content: center;
        margin-bottom: 2.5rem;
        animation: fadeInUp 1s ease 0.4s;
        animation-fill-mode: both;
    }

    .search-box form {
        display: flex;
        width: 100%;
        max-width: 600px;
    }
    
    .dropdown-form {
        width: 100%;
    }
    
    .custom-dropdown {
        display: flex;
        width: 100%;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        overflow: hidden;
    }
    
    .form-select {
        flex: 1;
        padding: 1rem 1.5rem;
        border: 2px solid rgba(0, 0, 0, 0.05);
        border-right: none;
        border-radius: 16px 0 0 16px;
        font-size: 1rem;
        outline: none;
        transition: all 0.3s ease;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url('data:image/svg+xml;utf8,<svg fill="%232563eb" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M7 10l5 5 5-5z"/></svg>');
        background-repeat: no-repeat;
        background-position: right 1rem center;
        background-size: 1.5rem;
        cursor: pointer;
        background-color: white;
        position: relative;
        z-index: 1;
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.02);
    }
    
    .form-select:hover {
        border-color: #3b82f6;
        box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    
    .form-select:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    .form-select option {
        padding: 10px;
    }

    .btn-search {
        background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
        color: white;
        border: none;
        padding: 1rem 1.5rem;
        border-radius: 0 16px 16px 0;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        min-width: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-search:hover {
        background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
    
    .btn-search:active {
        transform: translateY(0);
    }

    .search-box button::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 100%);
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }

    .search-box button:hover::after {
        transform: translateX(100%);
    }

    .services-list {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        margin-top: 2rem;
        padding: 0 1rem;
        justify-content: center;
    }

    .service-card {
        background: var(--white);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--card-shadow);
        transition: box-shadow 0.3s, transform 0.3s;
        border: none;
        margin-bottom: 1.5rem;
        animation: fadeInUp 0.8s ease;
    }

    .service-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--gradient-primary);
        transform: scaleX(0);
        transform-origin: left;
        transition: transform 0.3s ease;
    }

    .service-card:hover::before {
        transform: scaleX(1);
    }

    .service-card:hover {
        transform: translateY(-10px) scale(1.02);
        box-shadow: var(--hover-shadow);
    }

    .service-img {
        width: 100%;
        height: 250px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--background);
        font-size: 4rem;
        color: var(--light-text);
    }

    .service-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .service-card:hover .service-img img {
        transform: scale(1.05);
    }

    .service-info {
        padding: 1.5rem;
        text-align: center;
    }

    .service-info h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        color: var(--text-color);
    }

    .service-info p {
        color: var(--light-text);
        margin-bottom: 1rem;
    }

    .btn-view-all {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        margin-top: 1rem;
        text-decoration: none;
        background: var(--gradient-primary);
        color: white;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-view-all::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 100%);
        transform: translateX(-100%);
        transition: transform 0.6s ease;
    }

    .btn-view-all:hover::after {
        transform: translateX(100%);
    }

    .featured-consultant {
        margin-top: 1.5rem;
        padding: 2rem;
        background: linear-gradient(135deg, #f9f9f9, #f0f0f0);
        border-radius: 15px;
        text-align: center;
        border: 1px solid #eee;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .featured-consultant::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--gradient-primary);
    }

    .featured-consultant h3 {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: var(--text-color);
    }

    .featured-consultant p {
        margin-bottom: 0.5rem;
    }

    .featured-consultant .rate {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--primary-color);
    }

    .featured-consultant .bio {
        font-size: 0.95rem;
        color: var(--light-text);
        margin-bottom: 1rem;
    }

    .btn-book {
        display: inline-block;
        padding: 0.75rem 1.5rem;
        background: var(--gradient-secondary);
        color: white;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .btn-book::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(45deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 100%);
        transform: translateX(-100%);
        transition: transform 0.6s ease;
    }

    .btn-book:hover::after {
        transform: translateX(100%);
    }

    @media (max-width: 768px) {
        .main-heading {
            padding: 2rem;
            margin: 1rem;
        }

        .main-heading h1 {
            font-size: 2rem;
        }

        .services-list {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .search-box form {
            flex-direction: column;
            gap: 0.5rem;
        }

        .search-box input[type="text"],
        .search-box button {
            border-radius: 12px;
            width: 100%;
        }
    }

    @media (max-width: 992px) {
        .services-list {
            grid-template-columns: 1fr;
        }
    }

    /* Footer Styles */
    .footer {
        background: #4a90e2;
        color: #fff;
        padding: 4rem 0 2rem;
        margin-top: 4rem;
    }

    .footer h5, .footer h6 {
        color: #fff;
        font-weight: 600;
        margin-bottom: 1.5rem;
    }

    .footer .text-muted {
        color: rgba(255, 255, 255, 0.7) !important;
    }

    .footer a {
        transition: color 0.3s ease;
    }

    .footer a:hover {
        color: #fff !important;
    }

    .footer .social-links a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        transition: all 0.3s ease;
    }

    .footer .social-links a:hover {
        background: var(--primary-color);
        transform: translateY(-3px);
    }

    .footer hr {
        border-color: rgba(255, 255, 255, 0.1);
    }

    @media (max-width: 768px) {
        .footer {
            text-align: center;
        }
        
        .footer .text-md-start {
            text-align: center !important;
        }
        
        .footer .text-md-end {
            text-align: center !important;
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>Our Services</h1>
            <p>Expert guidance and support for your career growth — explore our featured services below.</p>
        </div>
    </section>
    <div class="page-container">
        <div class="container">
            <div class="main-heading">
                <h1>Our Cybersecurity Services</h1>
            </div>
            <p class="intro-text">Explore our comprehensive range of cybersecurity services offered by our expert consultants.</p>
            
            <div class="search-box">
                <form action="services.php" method="GET" class="dropdown-form" id="serviceForm">
                    <div class="custom-dropdown">
                        <select name="specialty" id="specialty-dropdown" class="form-select" onchange="this.form.submit()">
                            <option value="">Select a cybersecurity service</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?php echo htmlspecialchars($service['name']); ?>" <?php echo ($search_keyword === $service['name']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($service['name']); ?> (<?php echo $service['consultant_count']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-search">Find Service</button>
                    </div>
                </form>
            </div>
            
            <div class="services-list">
                <?php if ($search_keyword !== ''): ?>
                    <?php if (count($filtered_services) > 0): ?>
                        <?php foreach ($filtered_services as $service): ?>
                            <div class="service-card">
                                <div class="service-info">
                                    <h2><?php echo htmlspecialchars($service['name']); ?></h2>
                                    <p><strong><?php echo $service['consultant_count']; ?> consultant<?php echo $service['consultant_count'] != 1 ? 's' : ''; ?> available</strong></p>
                                    <?php if ($service['featured_consultant']): ?>
                                        <div class="featured-consultant">
                                            <h3>Featured Consultant</h3>
                                            <p><strong><?php echo htmlspecialchars($service['featured_consultant']['name']); ?></strong></p>
                                            <p class="rate">$<?php echo number_format($service['featured_consultant']['hourly_rate'], 2); ?>/hour</p>
                                            <p class="bio"><?php echo htmlspecialchars(substr($service['featured_consultant']['bio'], 0, 100)); ?>...</p>
                                            <a href="appointment.php?id=<?php echo $service['featured_consultant']['id']; ?>" class="btn-book">Book Now</a>
                                        </div>
                                    <?php endif; ?>
                                    <a href="consultants.php?specialty=<?php echo urlencode($service['name']); ?>" class="btn-view-all">View Consultants</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>We do not offer that service.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (count($services) > 0): ?>
                        <?php foreach ($services as $service): ?>
                            <div class="service-card">
                                <div class="service-info">
                                    <h2><?php echo htmlspecialchars($service['name']); ?></h2>
                                    <p><strong><?php echo $service['consultant_count']; ?> consultant<?php echo $service['consultant_count'] != 1 ? 's' : ''; ?> available</strong></p>
                                    <?php if ($service['featured_consultant']): ?>
                                        <div class="featured-consultant">
                                            <h3>Featured Consultant</h3>
                                            <p><strong><?php echo htmlspecialchars($service['featured_consultant']['name']); ?></strong></p>
                                            <p class="rate">$<?php echo number_format($service['featured_consultant']['hourly_rate'], 2); ?>/hour</p>
                                            <p class="bio"><?php echo htmlspecialchars(substr($service['featured_consultant']['bio'], 0, 100)); ?>...</p>
                                            <a href="appointment.php?id=<?php echo $service['featured_consultant']['id']; ?>" class="btn-book">Book Now</a>
                                        </div>
                                    <?php endif; ?>
                                    <a href="consultants.php?specialty=<?php echo urlencode($service['name']); ?>" class="btn-view-all">View All Consultants</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No services available at the moment. Please check back later.</p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Make sure the dropdown is working properly
        document.addEventListener('DOMContentLoaded', function() {
            const dropdown = document.getElementById('specialty-dropdown');
            
            // Add click event listener to ensure dropdown works
            dropdown.addEventListener('click', function() {
                // This ensures the dropdown gets focus when clicked
                this.focus();
            });
            
            // Make sure form submits when dropdown value changes
            dropdown.addEventListener('change', function() {
                document.getElementById('serviceForm').submit();
            });
            
            // Add visual cue that dropdown is clickable
            dropdown.addEventListener('mouseover', function() {
                this.style.borderColor = '#3b82f6';
            });
            
            dropdown.addEventListener('mouseout', function() {
                if (document.activeElement !== this) {
                    this.style.borderColor = 'rgba(0, 0, 0, 0.05)';
                }
            });
        });
    </script>
</body>
</html>