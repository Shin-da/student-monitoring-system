// Student Dashboard Animations and Charts
document.addEventListener('DOMContentLoaded', function() {
    // Animate counters
    function animateCounters() {
        const counters = document.querySelectorAll('[data-count-to]');
        counters.forEach(counter => {
            const target = parseFloat(counter.getAttribute('data-count-to'));
            const decimals = counter.getAttribute('data-count-decimals') || 0;
            const duration = 2000;
            const start = performance.now();
            
            function updateCounter(currentTime) {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                const current = progress * target;
                
                if (decimals > 0) {
                    counter.textContent = current.toFixed(decimals);
                } else {
                    counter.textContent = Math.floor(current);
                }
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }
            
            requestAnimationFrame(updateCounter);
        });
    }
    
    // Animate progress bars
    function animateProgressBars() {
        const progressBars = document.querySelectorAll('[data-progress-to]');
        progressBars.forEach(bar => {
            const target = parseFloat(bar.getAttribute('data-progress-to'));
            const duration = 1500;
            const start = performance.now();
            
            function updateProgress(currentTime) {
                const elapsed = currentTime - start;
                const progress = Math.min(elapsed / duration, 1);
                const current = progress * target;
                
                bar.style.width = current + '%';
                
                if (progress < 1) {
                    requestAnimationFrame(updateProgress);
                }
            }
            
            requestAnimationFrame(updateProgress);
        });
    }
    
    // Initialize performance chart
    function initPerformanceChart() {
        const ctx = document.getElementById('performanceChart');
        if (!ctx) return;
        
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['Q1 2023', 'Q2 2023', 'Q3 2023', 'Q4 2023', 'Q1 2024'],
                datasets: [{
                    label: 'Overall Average',
                    data: [78.5, 82.1, 80.3, 85.2, 85.2],
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#0d6efd',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 70,
                        max: 100,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        },
                        ticks: {
                            color: '#6c757d'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#6c757d'
                        }
                    }
                },
                elements: {
                    point: {
                        hoverBackgroundColor: '#0d6efd'
                    }
                }
            }
        });
    }
    
    // Add scroll animations
    function addScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe all cards
        document.querySelectorAll('.stat-card, .action-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    }
    
    // Load live student data panels
    function escapeHtml(t){ if(!t) return ''; const d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
    function groupByDay(items){ const order=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday']; const map={}; order.forEach(d=>map[d]=[]); items.forEach(i=>{ if(!map[i.day]) map[i.day]=[]; map[i.day].push(i); }); return order.map(d=>({day:d,items:map[d]||[]})); }

    function loadMyClasses(){
        var loading = document.getElementById('myClassesLoading');
        var empty = document.getElementById('myClassesEmpty');
        var list = document.getElementById('myClassesList');
        var count = document.getElementById('myClassesCount');
        if (!loading && !empty && !list && !count) return; // panel not present
        fetch((window.__BASE_PATH__ || '') + '/api/student/my-classes.php', { cache: 'no-cache' })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (loading) loading.style.display = 'none';
                if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                    if (empty) empty.style.display = 'block';
                    if (count) count.textContent = '';
                    return;
                }
                if (count) count.textContent = data.data.length + ' total';
                if (list) {
                    list.style.display = 'flex';
                    list.innerHTML = data.data.map(function(c){
                        return '\n          <div class="d-flex align-items-start p-3 border rounded">\n            <div class="bg-primary bg-opacity-10 rounded-circle p-2 me-3">\n              <svg width="16" height="16" class="text-primary" fill="currentColor"><use href="#icon-sections"></use></svg>\n            </div>\n            <div class="flex-grow-1">\n              <div class="fw-semibold">' + escapeHtml(c.subject) + ' <span class="text-muted small">(' + escapeHtml(c.section) + ')</span></div>\n              <div class="text-muted small">Teacher: ' + escapeHtml(c.teacher_name || '') + '</div>\n              <div class="text-muted small">Room: ' + escapeHtml(c.room || 'TBD') + '</div>\n              <div class="badge bg-info-subtle text-info mt-1">' + escapeHtml(c.schedule || '') + '</div>\n            </div>\n          </div>';
                    }).join('');
                }
            })
            .catch(function(){ if (loading) loading.textContent = 'Failed to load classes'; });
    }

    function loadMySchedule(){
        var loading = document.getElementById('myScheduleLoading');
        var empty = document.getElementById('myScheduleEmpty');
        var byDay = document.getElementById('myScheduleByDay');
        var meta = document.getElementById('myScheduleMeta');
        if (!loading && !empty && !byDay && !meta) return; // panel not present
        fetch((window.__BASE_PATH__ || '') + '/api/student/my-schedule.php', { cache: 'no-cache' })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (loading) loading.style.display = 'none';
                if (!data.success || !Array.isArray(data.data) || data.data.length === 0) {
                    if (empty) empty.style.display = 'block';
                    if (meta) meta.textContent = '';
                    return;
                }
                if (meta) meta.textContent = data.data.length + ' sessions';
                var grouped = groupByDay(data.data);
                if (byDay) {
                    byDay.style.display = 'block';
                    var html = '<div class="row">';
                    grouped.forEach(function(g){
                        html += '<div class="col-md-4 mb-3"><div class="p-3 border rounded">';
                        html += '<div class="fw-semibold mb-2">' + g.day + '</div>';
                        if (g.items.length === 0) {
                            html += '<small class="text-muted">No classes</small>';
                        } else {
                            g.items.forEach(function(it){
                                html += '\n                <div class="d-flex justify-content-between align-items-center py-1 border-bottom small">\n                  <span>' + it.start_ampm + '-' + it.end_ampm + '</span>\n                  <span class="text-muted">' + escapeHtml(it.subject_name || '') + '</span>\n                </div>';
                            });
                        }
                        html += '</div></div>';
                    });
                    html += '</div>';
                    byDay.innerHTML = html;
                }
            })
            .catch(function(){ if (loading) loading.textContent = 'Failed to load schedule'; });
    }

    // Initialize all animations
    setTimeout(() => {
        animateCounters();
        animateProgressBars();
        initPerformanceChart();
        addScrollAnimations();
        loadMyClasses();
        loadMySchedule();
    }, 500);
    
    // Add enhanced hover effects for cards
    document.querySelectorAll('.stat-card, .action-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.02)';
            this.style.boxShadow = '0 15px 35px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = 'none';
        });
    });
    
    // Add click animations for buttons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Create ripple effect
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
});

// Add CSS for ripple effect
const style = document.createElement('style');
style.textContent = `
    .btn {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
