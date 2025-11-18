<?php
// header.php
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Clínica Rodríguez y Especialistas</title>
  <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
  <!-- Topbar -->
  <div class="topbar">
    <div class="wrap">
      <div class="left">
        <span class="phone">📞 997 753 923</span>
        <span class="email">✉ recepcion.centroneuroquirurgico@gmail.com</span>
      </div>
      <div class="right">
        <a class="btn-cita" href="/reserva.php">Cita Online →</a>
        <span class="hours">⏱ 24x7 Atenciones</span>
      </div>
    </div>
  </div>

  <!-- Navbar -->
  <header class="navbar">
    <div class="wrap nav-inner">
      <div class="brand">
        <!-- Si tienes logo reemplaza por <img src="/assets/img/logo.png" alt="logo"> -->
        <h1>CLÍNICA <span>RODRÍGUEZ Y ESPECIALISTAS</span></h1>
      </div>

      <nav>
        <ul class="menu" id="menu">
          <li><a href="/index.php">Inicio</a></li>
          <li><a href="/medicos.php">Médicos</a></li>
          <li><a href="/especialidades.php">Especialidades</a></li>
          <li><a href="/servicios.php">Servicios</a></li>
          <li><a href="/nosotros.php">Nosotros</a></li>
          <li><a href="/contacto.php">Contacto</a></li>
          <li><a href="/reserva.php" class="menu-reserva">Reserva</a></li>
        </ul>
      </nav>

      <button id="menu-toggle" class="menu-toggle" aria-label="Abrir menú">☰</button>
    </div>
  </header>
