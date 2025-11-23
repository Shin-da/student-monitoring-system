<!-- Hero Section -->
<section class="hero-section py-5 mb-5">
  <div class="container-fluid container-narrow">
    <div class="row align-items-center g-4">
      <div class="col-lg-6 text-center text-lg-start">
        <div class="mb-4">
          <img src="<?= \Helpers\Url::asset('assets/images/logo/logo-full-transparent.png') ?>" alt="St. Ignatius Logo" class="hero-logo mb-4" style="max-width: 200px; height: auto;">
        </div>
        <h1 class="display-4 fw-bold mb-3">Student Monitoring System</h1>
        <p class="lead text-muted mb-4">A comprehensive platform for managing student information, attendance, grades, and academic progress at St. Ignatius.</p>
        <div class="d-flex flex-wrap gap-3 mb-4">
          <a href="<?= \Helpers\Url::to('/login') ?>" class="btn btn-primary btn-lg px-4">Sign In</a>
          <a href="<?= \Helpers\Url::to('/register') ?>" class="btn btn-outline-primary btn-lg px-4">Student Registration</a>
        </div>
        <div class="d-flex flex-wrap gap-4 text-muted small">
          <div>
            <strong class="text-dark d-block">Secure</strong>
            <span>Protected data</span>
          </div>
          <div>
            <strong class="text-dark d-block">Real-time</strong>
            <span>Live updates</span>
          </div>
          <div>
            <strong class="text-dark d-block">Accessible</strong>
            <span>Anywhere, anytime</span>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-image-wrapper text-center">
          <div class="surface rounded-4 p-4 p-md-5 shadow-sm">
            <div class="row g-3">
              <div class="col-6">
                <div class="card border-0 shadow-sm h-100">
                  <div class="card-body text-center p-4">
                    <div class="display-6 mb-2">📊</div>
                    <h6 class="mb-1">Grades</h6>
                    <p class="text-muted small mb-0">Track performance</p>
                  </div>
                </div>
              </div>
              <div class="col-6">
                <div class="card border-0 shadow-sm h-100">
                  <div class="card-body text-center p-4">
                    <div class="display-6 mb-2">📅</div>
                    <h6 class="mb-1">Attendance</h6>
                    <p class="text-muted small mb-0">Monitor daily</p>
                  </div>
                </div>
              </div>
              <div class="col-6">
                <div class="card border-0 shadow-sm h-100">
                  <div class="card-body text-center p-4">
                    <div class="display-6 mb-2">📝</div>
                    <h6 class="mb-1">Assignments</h6>
                    <p class="text-muted small mb-0">Stay organized</p>
                  </div>
                </div>
              </div>
              <div class="col-6">
                <div class="card border-0 shadow-sm h-100">
                  <div class="card-body text-center p-4">
                    <div class="display-6 mb-2">👥</div>
                    <h6 class="mb-1">Communication</h6>
                    <p class="text-muted small mb-0">Stay connected</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Features Section -->
<section class="features-section py-5 my-5">
  <div class="container-fluid container-narrow">
    <div class="text-center mb-5">
      <h2 class="h2 fw-bold mb-3">Everything You Need</h2>
      <p class="text-muted lead">Comprehensive tools for effective student management</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="feature-card surface h-100 p-4 rounded-3">
          <div class="feature-icon-wrapper mb-3">
            <div class="feature-icon bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
              <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24">
                <path d="M16 6l2.29 2.29l-4.88 4.88l-4-4L2 16.59L3.41 18l6-6l4 4l6.3-6.29L22 12V6z"/>
              </svg>
            </div>
          </div>
          <h3 class="h5 fw-semibold mb-2">Grade Management</h3>
          <p class="text-muted mb-0">Track and manage student grades with detailed analytics and progress reports.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card surface h-100 p-4 rounded-3">
          <div class="feature-icon-wrapper mb-3">
            <div class="feature-icon bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
              <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24">
                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
              </svg>
            </div>
          </div>
          <h3 class="h5 fw-semibold mb-2">Attendance Tracking</h3>
          <p class="text-muted mb-0">Real-time attendance monitoring with automated notifications and reports.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card surface h-100 p-4 rounded-3">
          <div class="feature-icon-wrapper mb-3">
            <div class="feature-icon bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
              <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24">
                <path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/>
              </svg>
            </div>
          </div>
          <h3 class="h5 fw-semibold mb-2">Assignment Management</h3>
          <p class="text-muted mb-0">Create, distribute, and track assignments with due dates and submissions.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="feature-card surface h-100 p-4 rounded-3">
          <div class="feature-icon-wrapper mb-3">
            <div class="feature-icon bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
              <svg width="28" height="28" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
              </svg>
            </div>
          </div>
          <h3 class="h5 fw-semibold mb-2">Parent Communication</h3>
          <p class="text-muted mb-0">Keep parents informed with real-time updates on their child's progress.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Access Points Section -->
<section class="access-section py-5 my-5">
  <div class="container-fluid container-narrow">
    <div class="text-center mb-5">
      <h2 class="h2 fw-bold mb-3">Access Your Portal</h2>
      <p class="text-muted">Choose your role to access the appropriate dashboard</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <a class="access-card d-block surface h-100 p-4 rounded-3 text-decoration-none text-center" href="<?= \Helpers\Url::to('/login') ?>">
          <div class="access-icon mb-3">
            <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
              <svg width="36" height="36" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10s10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5l1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
            </div>
          </div>
          <h3 class="h5 fw-semibold mb-2">Administrator</h3>
          <p class="text-muted small mb-0">System configuration and school-wide management</p>
        </a>
      </div>
      <div class="col-md-6 col-lg-3">
        <a class="access-card d-block surface h-100 p-4 rounded-3 text-decoration-none text-center" href="<?= \Helpers\Url::to('/login') ?>">
          <div class="access-icon mb-3">
            <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
              <svg width="36" height="36" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 22L12 18.77L5.82 22L7 14.14L2 9.27l6.91-1.01L12 2z"/>
              </svg>
            </div>
          </div>
          <h3 class="h5 fw-semibold mb-2">Teacher</h3>
          <p class="text-muted small mb-0">Manage classes, grades, and student progress</p>
        </a>
      </div>
      <div class="col-md-6 col-lg-3">
        <a class="access-card d-block surface h-100 p-4 rounded-3 text-decoration-none text-center" href="<?= \Helpers\Url::to('/login') ?>">
          <div class="access-icon mb-3">
            <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
              <svg width="36" height="36" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10s10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.94-.49-7-3.85-7-7.93c0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41c0 2.08-.8 3.97-2.1 5.39z"/>
              </svg>
            </div>
          </div>
          <h3 class="h5 fw-semibold mb-2">Adviser</h3>
          <p class="text-muted small mb-0">Monitor and guide student development</p>
        </a>
      </div>
      <div class="col-md-6 col-lg-3">
        <a class="access-card d-block surface h-100 p-4 rounded-3 text-decoration-none text-center" href="<?= \Helpers\Url::to('/login') ?>">
          <div class="access-icon mb-3">
            <div class="bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
              <svg width="36" height="36" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4s-4 1.79-4 4s1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </div>
          </div>
          <h3 class="h5 fw-semibold mb-2">Student / Parent</h3>
          <p class="text-muted small mb-0">View grades, attendance, and academic information</p>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Call to Action Section -->
<section class="cta-section py-5 my-5">
  <div class="container-fluid container-narrow">
    <div class="cta-card surface rounded-4 p-5 text-center">
      <h2 class="h3 fw-bold mb-3">Get Started Today</h2>
      <p class="text-muted mb-4">New students can register for an account, or existing users can sign in to access their dashboard.</p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        <a href="<?= \Helpers\Url::to('/register') ?>" class="btn btn-primary btn-lg px-5">Student Registration</a>
        <a href="<?= \Helpers\Url::to('/login') ?>" class="btn btn-outline-primary btn-lg px-5">Sign In</a>
      </div>
    </div>
  </div>
</section>

<style>
.hero-section {
  padding-top: 3rem;
  padding-bottom: 3rem;
}

.hero-logo {
  filter: drop-shadow(0 2px 8px rgba(0,0,0,0.1));
}

.hero-image-wrapper .card {
  transition: transform 0.2s ease;
}

.hero-image-wrapper .card:hover {
  transform: translateY(-4px);
}

.feature-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid var(--color-border);
}

.feature-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
}

.access-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  border: 1px solid var(--color-border);
  color: inherit;
}

.access-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0,0,0,0.1);
  color: inherit;
  text-decoration: none;
}

.cta-card {
  background: linear-gradient(135deg, var(--color-surface) 0%, var(--color-bg) 100%);
  border: 1px solid var(--color-border);
}

@media (max-width: 768px) {
  .hero-section {
    padding-top: 2rem;
    padding-bottom: 2rem;
  }
  
  .hero-logo {
    max-width: 150px !important;
  }
}
</style>
