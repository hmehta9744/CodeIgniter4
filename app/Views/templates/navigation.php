<nav class='navbar navbar-expand-lg d-md-flex navbar-light bg-light'>
    <div class='container-fluid'>
        <a class='navbar-brand' href='<?php echo base_url(); ?>'><img src='<?php echo base_url(); ?>images/la_barrigona_logo_sm.png' alt='Logotipo de la barrigona'></a>
        <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#navbarSupportedContent' aria-controls='navbarSupportedContent' aria-expanded='false' aria-label='Toggle navigation'>
            <span class='navbar-toggler-icon'></span>
        </button>
        <div class='collapse navbar-collapse' id='navbarSupportedContent'>
            <ul class='navbar-nav ms-auto mx-3 mb-lg-0 text-center fs-1'>
                <li class='nav-item p-2'>
                    <a class='nav-link active' aria-current='page' href='<?php echo base_url(); ?>'>Inicio</a>
                </li>
                <li class='nav-item p-2'>
                    <a class='nav-link' href='<?php echo base_url(); ?>#sobre-nosotros'>Sobre nosotros</a>
                </li>
                <li class='nav-item p-2'>
                    <a class='nav-link' href='<?php echo base_url(); ?>#menu'>Menu</a>
                </li>
                <li class='nav-item p-2 dropdown'>
                    <a class='nav-link dropdown-toggle' href='<?php echo base_url(); ?>' id='navbarDropdown' role='button' data-bs-toggle='dropdown' aria-expanded='false'>
                        ¿Trabajas Aqui?
                    </a>
                    <ul class='dropdown-menu me-auto text-center' aria-labelledby='navbarDropdown'>
                        <li data-bs-toggle='modal' data-bs-target='#empleo'>Cocineros</li>
                        <li><hr class='dropdown-divider'></li>
                        <li data-bs-toggle='modal' data-bs-target='#empleo'>Cajeros</li>
                        <li><hr class='dropdown-divider'></li>
                        <li data-bs-toggle='modal' data-bs-target='#empleo'>Meseros</li>
                    </ul>
                </li>
                <li class='nav-item p-2'>
                    <a class='nav-link' href='#contacto'>Contacto</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
