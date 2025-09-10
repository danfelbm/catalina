NOSACQ50

# CONTEXTO IMPORTANTE:
@kiro/specs/sistema-votaciones-digital/rules.md — reglas
@kiro/specs/sistema-votaciones-digital/design.md

# tu misión

Por favor modifica el sistema de Form Builder para que el campo de Opción Múltiple permita aparte de guardar la opción a elegir, también guardar un valor numérico (integral) por cada opción que pueda usarse después en cálculos y estadísticas. Por ejemplo:

Muy de acuerdo → 4
De acuerdo → 3
En desacuerdo → 2
Muy en desacuerdo → 1

Esto con el fin de que puedas pasar a la segunda tarea de esta misión: crear un formulario.

Por favor crea un php en /database/sql que se encargue de crear un formulario en el modelo formularios que tengao estas preguntas:

(columnas tabuladas)
```
PREGUNTA	TIPO DE CAMPO	OPCION 1	OPCION 2	OPCION 3	OPCION 4
He leído la introducción del cuestionario y me comprometo a completarlo bajo las condiciones descritas	SELECCIÓN MÚLTIPLE	Si	No		
Edad	SELECCIÓN MÚLTIPLE				
Usted es	SELECCIÓN MÚLTIPLE	Hombre	Mujer		
Tiene un cargo directivo, por ejemplo, gerente, jefe, supervisor, coordinador?	SELECCIÓN MÚLTIPLE	Si	No		
La dirección anima a los empleados a trabajar de acuerdo con las reglas de seguridad y salud en el trabajo incluso cuando los tiempos de trabajo son reducidos	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección se asegura de que todos reciban la información necesaria sobre seguridad.	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección no muestra interés cuando alguien es poco cuidadoso con la seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Para la dirección es más importante la seguridad que la producción	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección acepta que los empleados se arriesguen cuando los tiempos de trabajo son reducidos	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos aquí tenemos confianza en la capacidad de la dirección para manejar la seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección se asegura de que todos los problemas de seguridad que se detectan durante las inspecciones sean corregidos inmediatamente	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Cuando se detecta un riesgo, la dirección lo ignora y no hace nada	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección no tiene la capacidad de manejar la seguridad adecuadamente	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección se esfuerza para diseñar rutinas de seguridad que son significativas y que realmente funcionan	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección se asegura de que todos los trabajadores puedan participar en la seguridad en su trabajo	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección anima a los empleados a participar en las decisiones que afectan su seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección nunca tiene en cuenta las sugerencias de los empleados sobre la seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección se esfuerza para que todos los empleados tengan un alto nivel de competencia respecto a la seguridad y los riesgos	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección nunca pide a los empleados sus opiniones antes de tomar decisiones sobre la seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección involucra a los empleados en la toma de decisiones sobre la seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección recoge información precisa en las investigaciones sobre accidentes	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
El miedo a las sanciones (consecuencias negativas) de la dirección desanima a los empleados a informar sobre hechos que casi han provocado accidentes (incidentes)	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección escucha atentamente a todos los que han estado involucrados en un accidente	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección busca las causas, no a las personas culpables, cuando ocurre un accidente	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección siempre culpa de los accidentes a los empleados	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
La dirección trata a los empleados involucrados en un accidente de manera justa	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa nos esforzamos conjuntamente por alcanzar un alto nivel de seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa aceptamos conjuntamente la responsabilidad de asegurar que nuestro lugar de trabajo siempre esté ordenado	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
A quienes trabajamos en esta empresa no nos importa la seguridad de los demás	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa evitamos combatir los riesgos detectados	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa nos ayudamos mutuamente a trabajar seguros	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa no aceptamos ninguna responsabilidad por la seguridad de los demás	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa vemos los riesgos como algo que no se puede evitar	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa consideramos los accidentes menores como una parte normal de nuestro trabajo diario	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa aceptamos los comportamientos inseguros mientras no hayan accidentes	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa desobedecemos las reglas de seguridad para poder terminar el trabajo a tiempo	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa nunca aceptamos correr riesgos incluso cuando los tiempos de trabajo son reducidos	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa consideramos que nuestro trabajo no es adecuado para los miedosos	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa aceptamos correr riesgos en el trabajo	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa intentamos encontrar una solución si alguien nos indica un problema en la seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos aqu�os sentimos seguros cuando trabajamos juntos	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos aqu�enemos mucha confianza en nuestra mutua capacidad de garantizar la seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa aprendemos de nuestras experiencias para prevenir los accidentes	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa tomamos muy en serio las opiniones y sugerencias de los demás sobre la seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa rara vez hablamos sobre la seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa siempre hablamos de temas de seguridad cuando éstos surgen	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa podemos hablar libre y abiertamente sobre la seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa consideramos que un buen representante de seguridad juega un papel importante en la prevención de accidentes	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa consideramos que las inspecciones de seguridad no influyen en absoluto para generar seguridad	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa consideramos que la formación en seguridad es buena para prevenir accidentes	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa consideramos que la planificación anticipada de la seguridad no tiene sentido	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa consideramos que las inspecciones de seguridad ayudan a detectar riesgos importantes	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa consideramos que la formación en seguridad no tiene sentido	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Quienes trabajamos en esta empresa consideramos que es importante que haya objetivos de seguridad claros	SELECCIÓN MÚLTIPLE	Muy de acuerdo	De acuerdo	En desacuerdo	Muy en desacuerdo
Si desea ampliar alguna de sus respuestas, o tiene algn comentario sobre el estudio, puede escribirlo aquí	TEXTO				
```

Quiero que analices muy rigurosamente cómo funcionan los sistemas asociados a este desarrollo para que lo hagas bien.

Ultrathink.