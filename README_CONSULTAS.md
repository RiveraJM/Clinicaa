# 📋 MÓDULO DE CONSULTAS MÉDICAS

## 🎯 Descripción

Sistema completo de registro de historias clínicas y consultas médicas que permite documentar cada atención de manera estructurada, generar recetas médicas y llevar un historial completo por paciente.

## 📊 Estructura de una Consulta

### 1. Signos Vitales
- Presión arterial
- Frecuencia cardíaca
- Temperatura corporal
- Frecuencia respiratoria
- Peso y talla (con cálculo automático de IMC)
- Saturación de oxígeno

### 2. Anamnesis
- Motivo de consulta
- Enfermedad actual (tiempo de enfermedad, síntomas)
- Antecedentes personales (enfermedades previas, cirugías)
- Antecedentes familiares

### 3. Examen Físico
- Examen general (estado general, piel, mucosas)
- Examen regional/segmentario (por sistemas)

### 4. Diagnóstico
- Diagnóstico principal (CIE-10)
- Diagnósticos secundarios

### 5. Plan de Tratamiento
- Prescripción médica (medicamentos, dosis, frecuencia)
- Exámenes auxiliares solicitados
- Indicaciones generales

### 6. Seguimiento
- Requerimiento de control
- Días para próxima cita
- Observaciones de seguimiento

## 🔄 Flujo de Trabajo

### Registrar Consulta

1. **Desde Citas:**
   - Ir a `Citas → Lista de Citas`
   - Buscar cita confirmada o en atención
   - Clic en botón "Registrar Consulta" (icono 📋)

2. **Llenar Formulario:**
   - Información del paciente se carga automáticamente
   - Se muestra última consulta si existe
   - Llenar signos vitales (IMC se calcula automático)
   - Completar anamnesis y examen físico
   - Registrar diagnóstico (usar códigos CIE-10)
   - Prescribir tratamiento

3. **Guardar:**
   - **Guardar Consulta:** Solo guarda en el sistema
   - **Guardar e Imprimir:** Guarda y abre ventana de impresión

### Ver Historial del Paciente
```
Consultas → Lista → Clic en icono de Historial
```

Muestra:
- Todas las consultas del paciente
- Timeline ordenado por fecha
- Resumen de cada consulta
- Evolución de signos vitales

### Imprimir Documentos

**Consulta Completa:**
```
Consultas → Ver Consulta → Botón Imprimir
```

**Receta Médica:**
```
Consultas → Ver Consulta → Imprimir Receta
```

**Historial Completo:**
```
Historial del Paciente → Botón Imprimir Historial
```

## 🎨 Características Especiales

### Cálculo Automático de IMC
El sistema calcula automáticamente el IMC cuando se ingresa peso y talla:
- **< 18.5:** Bajo peso
- **18.5 - 24.9:** Normal
- **25 - 29.9:** Sobrepeso
- **≥ 30:** Obesidad

### Alertas de Alergias
Si el paciente tiene alergias registradas, se muestra una alerta destacada en:
- Formulario de registro de consulta
- Vista de consulta completa
- Historial del paciente

### Integración con Citas
- Al registrar consulta, la cita cambia automáticamente a estado "Atendida"
- Se registra hora de salida
- Se asocia la consulta con la cita original

### Control y Seguimiento
El sistema permite programar controles:
- Marcar si requiere control
- Especificar en cuántos días
- Agregar observaciones para próxima visita

## 📄 Formatos de Impresión

### Consulta Completa
Incluye:
- Header de la clínica
- Datos del paciente y médico
- Todos los signos vitales
- Anamnesis completa
- Examen físico
- Diagnóstico destacado
- Plan de tratamiento
- Firma del médico

### Receta Médica
Formato específico que incluye:
- Header de clínica
- Datos básicos del paciente
- Diagnóstico
- Prescripción (Rp/)
- Exámenes auxiliares
- Firma y sello del médico

## 🔍 Búsqueda y Filtros

### Lista de Consultas
Filtrar por:
- Rango de fechas
- Médico específico
- Búsqueda de paciente (DNI o nombre)

### Historial del Paciente
- Ordenado cronológicamente (más reciente primero)
- Timeline visual
- Resumen de cada consulta
- Acceso rápido a consulta completa

## 💾 Almacenamiento de Datos

Todos los datos se guardan en la tabla `consultas`:
```sql
- 56 campos estructurados
- Signos vitales numéricos
- Textos largos para descripciones
- Referencias a paciente, médico y cita
- Auditoría completa (quién registró, cuándo)
```

## 📊 Estadísticas Disponibles

En **Lista de Consultas:**
- Total de consultas en período
- Pacientes atendidos (únicos)
- Médicos activos

En **Historial del Paciente:**
- Total de consultas del paciente
- Última fecha de atención
- Especialidades en las que fue atendido

## 🔐 Seguridad y Privacidad

- Solo usuarios autenticados pueden acceder
- Registro de quién creó cada consulta
- Historial de auditoría completo
- Los datos no se pueden eliminar (solo consultar/crear)

## 💡 Mejores Prácticas

### Al Registrar Consulta:

1. **Verificar datos del paciente** antes de iniciar
2. **Revisar alergias** si están registradas
3. **Usar códigos CIE-10** en diagnósticos cuando sea posible
4. **Ser específico** en la prescripción (medicamento, dosis, frecuencia, duración)
5. **Incluir indicaciones claras** para el paciente

### Ejemplo de Prescripción Correcta:
```
1. Paracetamol 500mg
   Tomar 1 tableta cada 8 horas por 5 días
   
2. Amoxicilina 500mg
   Tomar 1 cápsula cada 8 horas por 7 días
   
3. Reposo relativo
4. Abundantes líquidos
5. Control en 7 días si persisten síntomas
```

## 🚀 Futuras Mejoras

- [ ] Plantillas de consulta por especialidad
- [ ] Integración con laboratorio (resultados)
- [ ] Gráficas de evolución de signos vitales
- [ ] Recetas electrónicas
- [ ] Firma digital
- [ ] Exportar historial a PDF
- [ ] Búsqueda por CIE-10
- [ ] Estadísticas de diagnósticos más comunes

## 📞 Soporte

Para dudas sobre el registro de consultas:
- Revisar esta documentación
- Consultar con el administrador del sistema
- Verificar permisos de usuario