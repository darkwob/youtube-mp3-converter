<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instagram Feed App</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(asset('assets/css/sado.css')); ?>">
</head>
<body>

    <div id="smooth-wrapper">
        <div id="smooth-content">
            <!-- Hero Section with Fullscreen Animations -->
<section id="hero" class="hero-section text-center d-flex align-items-center justify-content-center">
    <div class="hero-content">
        <h1 class="display-4">Unleash the Power of Creative Data Solutions</h1>
        <p class="lead">Seamless integration with Instagram for unmatched insights and automation.</p>
        <a class="btn btn-primary mt-4" href="<?php echo e(url('login/facebook')); ?>">Login with Facebook</a>
    </div>
    <div id="three-js-bg" class="three-js-bg"></div>
</section>

<!-- Paper Airplane Animation -->
<div id="paper-plane" class="floating-element">
    <lottie-player src="https://assets5.lottiefiles.com/packages/lf20_ytogjxgi.json" background="transparent" speed="1" loop autoplay></lottie-player>
</div>

<!-- Section 1: Overview of Services -->
<section id="overview" class="content-section">
    <div class="container">
        <div class="row">
            <div class="col-md-6 mb-4">
                <h2>Maximize Your Instagram Insights</h2>
                <p>Our platform leverages the Instagram Graph API to give you detailed insights about your followers, engagement, and content performance.</p>
            </div>
            <div class="col-md-6">
                <div id="lottie-animation-1" class="lottie-container"></div>
            </div>
        </div>
    </div>
</section>

<!-- Section 2: Audience Analytics -->
<section id="analytics" class="content-section">
    <div class="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <h2>Deep Audience Analysis</h2>
                <p>Understand your audience demographics, behaviors, and preferences to tailor your content and strategy accordingly.</p>
            </div>
        </div>
        <div class="row mt-5">
            <div class="col-md-4">
                <div class="service-card text-center bg-white">
                    <h4>Follower Demographics</h4>
                    <p>Discover who your followers are by age, gender, and location.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card text-center bg-white">
                    <h4>Engagement Metrics</h4>
                    <p>Track likes, comments, shares, and more in real-time.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card text-center bg-white">
                    <h4>Content Performance</h4>
                    <p>Find out what type of content resonates most with your audience.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section 3: Automated Content Publishing -->
<section id="publishing" class="content-section bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2>Schedule and Automate Your Posts</h2>
                <p>Automatically schedule and publish your content on Instagram. Save time and increase your reach by posting at optimal times.</p>
            </div>
            <div class="col-md-6">
                <div id="lottie-animation-2" class="lottie-container"></div>
            </div>
        </div>
    </div>
</section>

<!-- Section 4: Boost Your Posts -->
<section id="boosting" class="content-section">
    <div class="container text-center">
        <h2>Boost Your Best Content</h2>
        <p>Increase visibility by boosting your most engaging posts directly from the platform.</p>
        <button class="btn btn-secondary mt-4">Learn How Boosting Works</button>
    </div>
</section>

<!-- Section 5: Custom Reporting -->
<section id="reporting" class="content-section bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h2>Generate Custom Reports</h2>
                <p>Generate comprehensive reports with all key metrics to measure the performance of your Instagram presence.</p>
            </div>
            <div class="col-md-6">
                <div id="lottie-animation-3" class="lottie-container"></div>
            </div>
        </div>
    </div>
</section>

<!-- Section 6: Integrations -->
<section id="integrations" class="content-section">
    <div class="container">
        <h2 class="text-center mb-5">Seamless Integrations</h2>
        <div class="row text-center">
            <div class="col-md-4">
                <div class="service-card bg-white">
                    <h4>CRM Systems</h4>
                    <p>Connect with your CRM for better customer data management.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card bg-white">
                    <h4>Email Marketing</h4>
                    <p>Integrate with email marketing tools to enhance your campaigns.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card bg-white">
                    <h4>Other Social Platforms</h4>
                    <p>Combine data from different platforms for a unified view.</p>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- Footer -->
<footer class="bg-dark text-white text-center py-3">
    <p>&copy; <?php echo e(date('Y')); ?> Instagram Feed App. All rights reserved.</p>
</footer>

        </div>
      </div>

<!-- JavaScript libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.7.4/lottie.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.10.4/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.10.4/ScrollTrigger.min.js"></script>
<script src="https://assets.codepen.io/16327/ScrollSmoother.min.js?v=3.12.5g"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="<?php echo e(asset('assets/js/app.js')); ?>"></script>

</body>
</html>
<?php /**PATH C:\xampp\htdocs\resources\views/index.blade.php ENDPATH**/ ?>