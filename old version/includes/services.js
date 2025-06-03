// JavaScript for services.php
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any interactive elements
    
    // For example, you could add filtering functionality
    const searchInput = document.querySelector('.search-box input[type="text"]');
    const serviceCards = document.querySelectorAll('.service-card');
    
    if (searchInput && serviceCards.length > 0) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            serviceCards.forEach(card => {
                const serviceName = card.querySelector('h2').textContent.toLowerCase();
                
                if (serviceName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
    
    function loadServices() {
        fetch('/actions/get_services.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const servicesList = document.querySelector('.services-list');
                    servicesList.innerHTML = '';
                    
                    data.services.forEach(service => {
                        const serviceCard = document.createElement('div');
                        serviceCard.className = 'service-card';
                        serviceCard.innerHTML = `
                            <h2>${service.name}</h2>
                            <p><strong>${service.consultant_count} consultant${service.consultant_count != 1 ? 's' : ''} available</strong></p>
                            <a href="consultants.php?specialty=${encodeURIComponent(service.name)}" class="btn-view-all">View All Consultants</a>
                        `;
                        servicesList.appendChild(serviceCard);
                    });
                }
            })
            .catch(error => console.error('Error loading services:', error));
    }
    

});