-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-05-2025 a las 05:29:22
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `salud`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `afiliados`
--

CREATE TABLE `afiliados` (
  `id_afiliacion` int(11) NOT NULL,
  `doc_afiliadiado` bigint(20) NOT NULL,
  `fecha_afi` date NOT NULL,
  `id_eps` int(11) DEFAULT NULL,
  `id_regimen` int(11) DEFAULT NULL,
  `id_arl` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `arl`
--

CREATE TABLE `arl` (
  `id_arl` int(11) NOT NULL,
  `nom_arl` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `arl`
--

INSERT INTO `arl` (`id_arl`, `nom_arl`) VALUES
(1, 'Positiva Compañia de Seguros S.A.'),
(2, 'ARL Sura'),
(3, 'Colmena Seguros'),
(4, 'Seguros Bolivar S.A.'),
(5, 'Seguros de Vida Alfa S.A.'),
(6, 'La Equidad Seguros O.C.'),
(7, 'Seguros de Vida Aurora S.A.'),
(8, 'No aplica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignacion_medico`
--

CREATE TABLE `asignacion_medico` (
  `id_asignacion` int(11) NOT NULL,
  `doc_medico` bigint(20) NOT NULL,
  `nit_ips` bigint(20) DEFAULT NULL,
  `id_estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `barrio`
--

CREATE TABLE `barrio` (
  `id_barrio` int(11) NOT NULL,
  `nom_barrio` varchar(100) NOT NULL,
  `id_mun` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `barrio`
--

INSERT INTO `barrio` (`id_barrio`, `nom_barrio`, `id_mun`) VALUES
(2, 'Picaleña', '05001'),
(3, 'El Vergel', '05001'),
(4, 'Los Tunos', '05001'),
(5, 'Brisas Del Pedregal', '05001'),
(6, 'Las Brisas', '05001'),
(7, 'Centro', '05001'),
(8, 'Interlaken', '05001'),
(9, 'La Pola', '05001'),
(10, 'Belén', '05001'),
(11, 'Centenario', '05001'),
(12, 'Cádiz', '05001'),
(13, 'Macarena Parte Alta', '05001'),
(14, 'Macarena Parte Baja', '05001'),
(15, 'Piedra Pintada', '05001'),
(16, 'Limonar', '05001'),
(17, 'Gaitan', '05001'),
(18, 'Jordan', '05001'),
(19, 'Prados Del Norte', '05001'),
(20, 'Ambalá', '05001'),
(21, 'La Gaviota', '05001'),
(22, 'El Salado', '05001'),
(23, 'Modelia', '05001'),
(24, 'Palermo', '05001'),
(25, 'Topacio', '05001'),
(26, 'Jardín Santander', '05001'),
(27, 'Varsovia', '05001'),
(28, 'Valparaiso', '05001'),
(29, 'Arkambuco', '05001'),
(30, 'La Esmeralda', '05001'),
(31, 'Kennedy', '05001'),
(32, 'Ricaurte', '05001'),
(33, 'Murillo Toro', '05001'),
(34, 'Uribe Uribe', '05001'),
(35, 'La Francia', '05001'),
(36, 'San Simón', '05001'),
(37, 'Hipódromo', '05001'),
(38, 'Departamental', '05001'),
(39, 'Granada', '05001');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id_cita` int(11) NOT NULL,
  `doc_pac` bigint(20) NOT NULL,
  `doc_med` bigint(20) NOT NULL,
  `nit_IPS` bigint(20) NOT NULL,
  `fecha_solici` date NOT NULL,
  `fecha_cita` date NOT NULL,
  `hora_cita` time NOT NULL,
  `id_est` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `departamento`
--

CREATE TABLE `departamento` (
  `id_dep` varchar(10) NOT NULL,
  `nom_dep` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `departamento`
--

INSERT INTO `departamento` (`id_dep`, `nom_dep`) VALUES
('05', 'Antioquia'),
('08', 'Atlantico'),
('11', 'Santa Fe de Bogota'),
('13', 'Bolivar'),
('15', 'Boyaca'),
('17', 'Caldas'),
('18', 'Caqueta'),
('19', 'Cauca'),
('20', 'Cesar'),
('23', 'Cordoba'),
('25', 'Cundinamarca'),
('27', 'Choco'),
('41', 'Huila'),
('44', 'Guajira'),
('47', 'Magdalena'),
('50', 'Meta'),
('52', 'Narino'),
('54', 'Norte de Santander'),
('63', 'Quindio'),
('66', 'Risaralda'),
('68', 'Santander'),
('70', 'Sucre'),
('73', 'Tolima'),
('76', 'Valle'),
('81', 'Arauca'),
('85', 'Casanare'),
('86', 'Putumayo'),
('88', 'San Andres'),
('91', 'Amazonas'),
('94', 'Guainia'),
('95', 'Guaviare'),
('97', 'Vaupes'),
('99', 'Vichada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_enfermedades_tipo_enfermedades`
--

CREATE TABLE `detalles_enfermedades_tipo_enfermedades` (
  `id_detalle_enfer` int(11) NOT NULL,
  `id_enferme` int(11) NOT NULL,
  `id_tipo_enfer` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_histo_clini`
--

CREATE TABLE `detalles_histo_clini` (
  `id_detalle` int(11) NOT NULL,
  `id_historia` int(11) NOT NULL,
  `id_diagnostico` int(11) NOT NULL,
  `id_enferme` int(11) NOT NULL,
  `id_medicam` int(11) NOT NULL,
  `can_medica` int(11) NOT NULL,
  `id_proced` int(11) NOT NULL,
  `cant_proced` int(11) NOT NULL,
  `prescripcion` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_eps_farm`
--

CREATE TABLE `detalle_eps_farm` (
  `id_eps_farm` int(11) NOT NULL,
  `nit_eps` int(11) NOT NULL,
  `nit_farm` bigint(20) NOT NULL,
  `fecha` date NOT NULL,
  `id_estado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_eps_ips`
--

CREATE TABLE `detalle_eps_ips` (
  `id_eps_ips` int(11) NOT NULL,
  `nit_eps` int(11) NOT NULL,
  `nit_ips` bigint(20) NOT NULL,
  `fecha` date NOT NULL,
  `id_estado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diagnostico`
--

CREATE TABLE `diagnostico` (
  `id_diagnos` int(11) NOT NULL,
  `diagnostico` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `diagnostico`
--

INSERT INTO `diagnostico` (`id_diagnos`, `diagnostico`) VALUES
(1, 'Rinofaringitis aguda'),
(2, 'Hipertensión arterial esencial (Primaria)'),
(3, 'Diabetes Mellitus tipo 2, controlada'),
(4, 'Cefalea tensional'),
(5, 'Gastritis aguda'),
(6, 'Infección del tracto urinario (ITU) no complicada'),
(7, 'Lumbalgia mecánica'),
(8, 'Dermatitis de contacto alérgica'),
(9, 'Faringoamigdalitis aguda viral'),
(10, 'Control de niño sano'),
(11, 'Esguince de tobillo grado I'),
(12, 'Migraña sin aura'),
(13, 'Anemia ferropénica leve'),
(14, 'Enfermedad por reflujo gastroesofágico (ERGE)'),
(15, 'Otitis media aguda'),
(16, 'Hipotiroidismo primario, en tratamiento'),
(17, 'Trastorno de ansiedad generalizada'),
(18, 'Chequeo general sin hallazgos patológicos'),
(19, 'Bronquitis aguda'),
(20, 'Urticaria aguda'),
(21, 'Vértigo posicional paroxístico benigno (VPPB)'),
(22, 'Gastroenteritis aguda probablemente viral'),
(23, 'Conjuntivitis alérgica'),
(24, 'Síndrome del intestino irritable'),
(25, 'Control prenatal, embarazo normoevolutivo'),
(26, 'Examen médico periódico de rutina'),
(27, 'Obesidad grado I'),
(28, 'Dislipidemia mixta'),
(29, 'Tendinitis del manguito rotador'),
(30, 'Candidiasis vulvovaginal'),
(31, 'Seguimiento de enfermedad crónica estable'),
(32, 'Asma bronquial persistente leve'),
(33, 'Evaluación preoperatoria'),
(34, 'Herpes Zoster'),
(35, 'Depresión leve a moderada'),
(36, 'Hemorroides internas grado II'),
(37, 'Osteoartritis de rodilla'),
(38, 'Sinusitis aguda'),
(39, 'Tinea pedis'),
(40, 'Control postoperatorio sin complicaciones'),
(41, 'Insomnio primario'),
(42, 'Impetigo contagioso'),
(43, 'Vacunación / Inmunización'),
(44, 'Consejería sobre planificación familiar'),
(45, 'Epicondilitis lateral'),
(46, 'Sin diagnóstico definitivo'),
(47, 'Diagnóstico pendiente de resultados'),
(48, 'Consulta de seguimiento, sin cambios'),
(49, 'No aplica diagnóstico (Consulta administrativa)'),
(50, 'Paciente asintomático, busca segunda opinión');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `enfermedades`
--

CREATE TABLE `enfermedades` (
  `id_enferme` int(11) NOT NULL,
  `nom_enfer` varchar(150) NOT NULL,
  `id_tipo_enfer` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `enfermedades`
--

INSERT INTO `enfermedades` (`id_enferme`, `nom_enfer`, `id_tipo_enfer`) VALUES
(1, 'Asma', 1),
(2, 'Bronquitis Cronica', 1),
(3, 'Neumonia', 1),
(4, 'Enfermedad Pulmonar Obstructiva Cronica (EPOC)', 1),
(5, 'Fibrosis Pulmonar', 1),
(6, 'Rinitis Alergica', 1),
(7, 'Hipertension Arterial', 2),
(8, 'Infarto Agudo de Miocardio', 2),
(9, 'Insuficiencia Cardiaca Congestiva', 2),
(10, 'Accidente Cerebrovascular (ACV)', 2),
(11, 'Arritmia Cardiaca', 2),
(12, 'Enfermedad Coronaria', 2),
(13, 'Epilepsia', 3),
(14, 'Enfermedad de Alzheimer', 3),
(15, 'Enfermedad de Parkinson', 3),
(16, 'Esclerosis Multiple', 3),
(17, 'Migrana Cronica', 3),
(18, 'Neuropatia Periferica', 3),
(19, 'Gastritis Cronica', 4),
(20, 'Ulcera Peptica (Gastrica o Duodenal)', 4),
(21, 'Sindrome del Intestino Irritable (Colon Irritable)', 4),
(22, 'Enfermedad de Crohn', 4),
(23, 'Colitis Ulcerosa', 4),
(24, 'Reflujo Gastroesofagico (ERGE)', 4),
(25, 'Enfermedad Celiaca', 4),
(26, 'Diabetes Mellitus Tipo 1', 5),
(27, 'Diabetes Mellitus Tipo 2', 5),
(28, 'Hipotiroidismo', 5),
(29, 'Hipertiroidismo', 5),
(30, 'Sindrome de Ovario Poliquistico (SOP)', 5),
(31, 'Enfermedad de Addison', 5),
(32, 'Insuficiencia Renal Cronica', 6),
(33, 'Calculos Renales (Litiasis Renal)', 6),
(34, 'Infeccion del Tracto Urinario (ITU) Recurrente', 6),
(35, 'Hiperplasia Prostatica Benigna (HPB)', 6),
(36, 'Enfermedad Renal Poliquistica', 6),
(37, 'Psoriasis', 7),
(38, 'Dermatitis Atopica (Eczema)', 7),
(39, 'Acne Severo', 7),
(40, 'Rosacea', 7),
(41, 'Urticaria Cronica', 7),
(42, 'Artritis Reumatoide', 8),
(43, 'Osteoartritis (Artrosis)', 8),
(44, 'Lupus Eritematoso Sistemico (LES)', 8),
(45, 'Fibromialgia', 8),
(46, 'Gota', 8),
(47, 'Osteoporosis', 8),
(48, 'Anemia Ferropenica', 9),
(49, 'Anemia Falciforme', 9),
(50, 'Leucemia', 9),
(51, 'Hemofilia', 9),
(52, 'Trombocitopenia', 9),
(53, 'VIH/SIDA', 10),
(54, 'Tuberculosis Pulmonar', 10),
(55, 'Hepatitis B Cronica', 10),
(56, 'Hepatitis C Cronica', 10),
(57, 'Dengue Grave', 10),
(58, 'Malaria (Paludismo)', 10),
(59, 'Enfermedad de Chagas', 10),
(60, 'Gripe (Influenza)', 10),
(61, 'COVID-19', 10),
(62, 'Cancer de Pulmon', 11),
(63, 'Cancer de Mama', 11),
(64, 'Cancer de Prostata', 11),
(65, 'Cancer Colorrectal', 11),
(66, 'Cancer Gastrico', 11),
(67, 'Linfoma', 11),
(68, 'Melanoma', 11),
(69, 'Depresion Mayor', 12),
(70, 'Trastorno de Ansiedad Generalizada (TAG)', 12),
(71, 'Trastorno Bipolar', 12),
(72, 'Esquizofrenia', 12),
(73, 'Trastorno Obsesivo-Compulsivo (TOC)', 12),
(74, 'Trastornos de la Conducta Alimentaria (Anorexia, Bulimia)', 12),
(75, 'Fibrosis Quistica', 13),
(76, 'Sindrome de Down', 13),
(77, 'Distrofia Muscular de Duchenne', 13),
(78, 'Enfermedad de Huntington', 13),
(79, 'Fenilcetonuria (PKU)', 13),
(80, 'Artritis Reumatoide (Autoinmune)', 14),
(81, 'Lupus Eritematoso Sistemico (LES) (Autoinmune)', 14),
(82, 'Esclerosis Sistemica (Esclerodermia)', 14),
(83, 'Enfermedad Celiaca (Autoinmune)', 14),
(84, 'Tiroiditis de Hashimoto', 14),
(85, 'Traumatismo Craneoencefalico Severo', 15),
(86, 'Quemaduras Graves (Tercer Grado)', 15),
(87, 'Intoxicacion por Plomo (Saturnismo)', 15),
(88, 'Lesion Medular', 15),
(89, 'Fracturas Multiples', 15),
(90, 'Anafilaxia', 16),
(91, 'Asma Alergica Severa', 16),
(92, 'Rinitis Alergica Persistente', 16),
(93, 'Dermatitis de Contacto Alergica', 16),
(94, 'Inmunodeficiencia Comun Variable (IDCV)', 16),
(95, 'Glaucoma', 17),
(96, 'Cataratas', 17),
(97, 'Retinopatia Diabetica', 17),
(98, 'Degeneracion Macular Asociada a la Edad (DMAE)', 17),
(99, 'Uveitis', 17),
(100, 'Otitis Media Cronica', 18),
(101, 'Sinusitis Cronica', 18),
(102, 'Amigdalitis Recurrente', 18),
(103, 'Perdida Auditiva (Hipoacusia)', 18),
(104, 'Polipos Nasales', 18),
(105, 'Obesidad Morbida', 19),
(106, 'Desnutricion Severa', 19),
(107, 'Deficiencia de Vitamina D Severa', 19),
(108, 'Dislipidemia (Colesterol/Trigliceridos Altos)', 19),
(109, 'Gota (Metabolica)', 19),
(110, 'Endometriosis Severa', 20),
(111, 'Miomas Uterinos Sintomaticos', 20),
(112, 'Preeclampsia / Eclampsia', 20),
(113, 'Infertilidad', 20),
(114, 'Cancer de Cuello Uterino', 20);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eps`
--

CREATE TABLE `eps` (
  `nit_eps` int(11) NOT NULL,
  `nombre_eps` varchar(100) NOT NULL,
  `direc_eps` varchar(150) NOT NULL,
  `nom_gerente` varchar(100) NOT NULL,
  `telefono` varchar(12) NOT NULL,
  `correo` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `eps`
--

INSERT INTO `eps` (`nit_eps`, `nombre_eps`, `direc_eps`, `nom_gerente`, `telefono`, `correo`) VALUES
(800088702, 'EPS SURA', 'Cra 43A # 1-50, Medellin', 'Andres Gomez', '3157654321', 'contacto@epssura.com.co'),
(800112806, 'FONDO DE PASIVO SOCIAL DE FERROCARRILES NACIONALES DE COLOMBIA', 'Diagonal 25G # 95A-85, Bogota', 'Mario Moreno', '6014285555', 'contacto@ferrocarriles.gov.co'),
(800130907, 'SALUD TOTAL EPS S.A.', 'Transversal 23 # 97-73, Bogota', 'Luis Rodriguez', '3201234567', 'cliente@saludtotal.com.co'),
(800251440, 'EPS SANITAS', 'Av. Calle 26 # 69-76, Bogota', 'Sofia Hernandez', '6013330011', 'servicio@epssanitas.com'),
(805001157, 'SERVICIO OCCIDENTAL DE SALUD EPS SOS', 'Calle 5 # 38-14, Cali', 'Pedro Sanchez', '3018765432', 'soporte@sos.com.co'),
(806008394, 'MUTUAL SER', 'Cra 10 # 20-30, Barranquilla', 'Carlos Lopez', '3109876543', 'gerencia@mutualser.com'),
(809008362, 'PIJAOS SALUD EPSI', 'Carrera 3 # 10-51, Ibague', 'Beatriz Campos', '3151122334', 'gerencia@pijaossalud.com'),
(817001773, 'ASOCIACION INDIGENA DEL CAUCA EPSI', 'Calle 5 # 9-25, Popayan', 'Jose Quilcue', '3101234567', 'info@aic.org.co'),
(824001398, 'DUSAKAWI EPSI', 'Calle 15 # 7-45, Valledupar', 'Arturo Arrieta', '3138765432', 'direccion@dusakawi.com'),
(830003564, 'FAMISANAR', 'Calle 72 # 10-07, Bogota', 'Elena Diaz', '6015551234', 'info@famisanar.com.co'),
(830113831, 'ALIANSALUD EPS', 'Calle 100 # 19-54, Bogota', 'Ana Martinez', '6017654321', 'atencion@aliansalud.co'),
(837000084, 'MALLAMAS EPSI', 'Carrera 25 # 18-45, Pasto', 'Luis Guanga', '3128765432', 'mallamas@mallamasepsi.com'),
(839000495, 'ANAS WAYUU EPSI', 'Calle 12 # 8-30, Riohacha', 'Maria Epiayu', '3209876543', 'contacto@anaswayuu.org'),
(860066942, 'COMPENSAR EPS', 'Av. 68 # 49A-47, Bogota', 'Laura Jimenez', '6013077001', 'afiliaciones@compensar.com'),
(890102044, 'CAJACOPI ATLANTICO', 'Calle 44 # 46-32, Barranquilla', 'Catalina Vargas', '3219871234', 'info@cajacopi.com'),
(890303093, 'COMFENALCO VALLE', 'Calle 5 # 6-63, Cali', 'Jorge Ramirez', '3112345678', 'eps@comfenalcovalle.com.co'),
(890500675, 'COMFAORIENTE', 'Av 1 # 16-48, Cucuta', 'Roberto Mendoza', '3187654321', 'servicio@comfaoriente.com'),
(890904996, 'EPM - EMPRESAS PUBLICAS DE MEDELLIN', 'Carrera 58 # 42-125, Medellin', 'Ricardo Alvarez', '6044441234', 'epm@epm.com.co'),
(891600091, 'COMFACHOCO', 'Cra 1 # 28-59, Quibdo', 'Patricia Cordoba', '3005556677', 'direccion@comfachoco.com.co'),
(891856000, 'CAPRESOCA', 'Carrera 21 # 6-25, Yopal', 'Francisco Pena', '3141239876', 'atencion@capresoca.gov.co'),
(900156264, 'NUEVA EPS', 'Av. Siempre Viva 742, Bogota', 'Maria Garcia', '6014445566', 'info@nuevaeps.com'),
(900226715, 'COOSALUD EPS-S', 'Calle Falsa 123, Bogota', 'Juan Perez', '3001112233', 'contacto@coosalud.com'),
(900298372, 'CAPITAL SALUD EPS-S', 'Ak. 14 # 43-65, Bogota', 'Hugo Castro', '6019191919', 'pqr@capitalsalud.gov.co'),
(900604350, 'SAVIA SALUD EPS', 'Circular 73 # 39-19, Medellin', 'Natalia Rios', '3023456789', 'contacto@saviasaludeps.com'),
(900914254, 'SALUD MIA', 'Carrera 7 # 71-21, Bogota', 'Isabel Torres', '6012123456', 'gerencia@saludmia.com'),
(900935126, 'ASMET SALUD', 'Calle 21 Norte # 3N-47, Popayan', 'Fernando Morales', '3171112233', 'info@asmetsalud.com'),
(901021565, 'EMSSANAR E.S.S.', 'Carrera 3 # 11-36, Pasto', 'Gabriela Leon', '3160001122', 'gerencia@emssanar.org.co'),
(901438242, 'SALUD BOLIVAR EPS SAS', 'Av. El Dorado # 68C-61, Bogota', 'Sergio Ortiz', '6017421000', 'cliente@saludbolivar.com'),
(901543761, 'EPS FAMILIAR DE COLOMBIA', 'Carrera 15 # 93-60, Bogota', 'Diana Rojas', '6018889900', 'contacto@epsfamiliar.org');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `especialidad`
--

CREATE TABLE `especialidad` (
  `id_espe` int(11) NOT NULL,
  `nom_espe` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `especialidad`
--

INSERT INTO `especialidad` (`id_espe`, `nom_espe`) VALUES
(1, 'Alergologia'),
(2, 'Anestesiologia y Reanimacion'),
(3, 'Angiologia y Cirugia Vascular'),
(4, 'Cardiologia'),
(5, 'Cirugia Cardiovascular'),
(6, 'Cirugia General y del Aparato Digestivo'),
(7, 'Cirugia Oral y Maxilofacial'),
(8, 'Cirugia Ortopedica y Traumatologia'),
(9, 'Cirugia Pediatrica'),
(10, 'Cirugia Plastica, Estetica y Reparadora'),
(11, 'Cirugia Toracica'),
(12, 'Dermatologia Medico-Quirurgica y Venereologia'),
(13, 'Endocrinologia y Nutricion'),
(14, 'Farmacologia Clinica'),
(15, 'Gastroenterologia'),
(16, 'Genetica Clinica'),
(17, 'Geriatria'),
(18, 'Ginecologia y Obstetricia'),
(19, 'Hematologia y Hemoterapia'),
(20, 'Inmunologia'),
(21, 'Medicina de Urgencias y Emergencias'),
(22, 'Medicina del Trabajo'),
(23, 'Medicina Familiar y Comunitaria'),
(24, 'Medicina Fisica y Rehabilitacion'),
(25, 'Medicina Intensiva'),
(26, 'Medicina Interna'),
(27, 'Medicina Legal y Forense'),
(28, 'Medicina Nuclear'),
(29, 'Medicina Preventiva y Salud Publica'),
(30, 'Microbiologia y Parasitologia'),
(31, 'Nefrologia'),
(32, 'Neumologia'),
(33, 'Neurocirugia'),
(34, 'Neurofisiologia Clinica'),
(35, 'Neurologia'),
(36, 'Oftalmologia'),
(37, 'Oncologia Medica'),
(38, 'Oncologia Radioterapica'),
(39, 'Otorrinolaringologia'),
(40, 'Pediatria'),
(41, 'Psiquiatria'),
(42, 'Radiodiagnostico'),
(43, 'Reumatologia'),
(44, 'Urologia'),
(45, 'Medicina General'),
(46, 'No aplica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estado`
--

CREATE TABLE `estado` (
  `id_est` int(11) NOT NULL,
  `nom_est` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estado`
--

INSERT INTO `estado` (`id_est`, `nom_est`) VALUES
(1, 'activo'),
(2, 'inactivo'),
(3, 'Asignada'),
(4, 'no asiganada');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `farmacias`
--

CREATE TABLE `farmacias` (
  `nit_farm` bigint(20) NOT NULL,
  `nom_farm` varchar(100) NOT NULL,
  `direc_farm` varchar(150) NOT NULL,
  `nom_gerente` varchar(100) NOT NULL,
  `tel_farm` varchar(12) NOT NULL,
  `correo_farm` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `farmacias`
--

INSERT INTO `farmacias` (`nit_farm`, `nom_farm`, `direc_farm`, `nom_gerente`, `tel_farm`, `correo_farm`) VALUES
(800149695, 'Cruz Verde', 'Avenida 6N # 25-50, Cali', 'Sofia Calderon', '602 1122334', 'servicioalcliente.cali@cruzverde.co'),
(800232121, 'Drogueria Familiar', 'Calle Falsa 123, Pereira', 'Ricardo Montoya', '606 2233445', 'drog.familiar.eje@email.com'),
(816001182, 'Audifarma', 'Carrera 45 # 80-12, Medellin', 'Carlos Restrepo', '604 9876543', 'contacto.med@audifarma.com.co'),
(860007336, 'Colsubsidio', 'Calle 100 # 15-20, Bogota', 'Ana Maria Velez', '601 3456789', 'gerente.colsub1@colsubsidio.com'),
(860013570, 'Cafam', 'Transversal 93 # 51-98, Bogota', 'Jorge Ramirez', '601 7788990', 'drogueria.cafam.ppal@cafam.com.co'),
(900432887, 'Farmart', 'Carrera 10 # 5-15, Bucaramanga', 'Elena Patricia Solano', '607 6655443', 'gerencia.bucara@farmart.co'),
(900580962, 'Disfarma', 'Calle 72 # 50-30, Barranquilla', 'Luis Fernando Diaz', '605 4455667', 'admin.bq@disfarma.com');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `genero`
--

CREATE TABLE `genero` (
  `id_gen` int(11) NOT NULL,
  `nom_gen` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `genero`
--

INSERT INTO `genero` (`id_gen`, `nom_gen`) VALUES
(1, 'Masculino'),
(2, 'Femenino'),
(3, 'No Binario'),
(4, 'Otro'),
(5, 'Prefiero no decir');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historia_clinica`
--

CREATE TABLE `historia_clinica` (
  `id_historia` int(11) NOT NULL,
  `id_cita` int(11) NOT NULL,
  `motivo_de_cons` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario_examen`
--

CREATE TABLE `horario_examen` (
  `id_horario_exan` int(11) NOT NULL,
  `horario` time NOT NULL,
  `meridiano` int(11) NOT NULL,
  `id_estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horario_examen`
--

INSERT INTO `horario_examen` (`id_horario_exan`, `horario`, `meridiano`, `id_estado`) VALUES
(1, '06:00:00', 1, 4),
(2, '06:20:00', 1, 4),
(3, '06:40:00', 1, 4),
(4, '07:00:00', 1, 4),
(5, '07:20:00', 1, 4),
(6, '07:40:00', 1, 4),
(7, '08:00:00', 1, 4),
(8, '08:20:00', 1, 4),
(9, '08:40:00', 1, 4),
(10, '09:00:00', 1, 4),
(11, '09:20:00', 1, 4),
(12, '09:40:00', 1, 4),
(13, '10:00:00', 1, 4),
(14, '10:20:00', 1, 4),
(15, '10:40:00', 1, 4),
(16, '11:00:00', 1, 4),
(17, '11:20:00', 1, 4),
(18, '11:40:00', 1, 4),
(19, '12:00:00', 2, 4),
(20, '12:20:00', 2, 4),
(21, '12:40:00', 2, 4),
(22, '01:00:00', 2, 4),
(23, '01:20:00', 2, 4),
(24, '01:40:00', 2, 4),
(25, '02:00:00', 2, 4),
(26, '02:20:00', 2, 4),
(27, '02:40:00', 2, 4),
(28, '03:00:00', 2, 4),
(29, '03:20:00', 2, 4),
(30, '03:40:00', 2, 4),
(31, '04:00:00', 2, 4),
(32, '04:20:00', 2, 4),
(33, '04:40:00', 2, 4),
(34, '05:00:00', 2, 4),
(35, '05:20:00', 2, 4),
(36, '05:40:00', 2, 4),
(37, '06:00:00', 2, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario_farm`
--

CREATE TABLE `horario_farm` (
  `id_horario_farm` int(11) NOT NULL,
  `horario` time NOT NULL,
  `meridiano` int(11) NOT NULL,
  `id_estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `horario_farm`
--

INSERT INTO `horario_farm` (`id_horario_farm`, `horario`, `meridiano`, `id_estado`) VALUES
(1, '06:00:00', 1, 4),
(2, '06:15:00', 1, 4),
(3, '06:30:00', 1, 4),
(4, '06:45:00', 1, 4),
(5, '07:00:00', 1, 4),
(6, '07:15:00', 1, 4),
(7, '07:30:00', 1, 4),
(8, '07:45:00', 1, 4),
(9, '08:00:00', 1, 4),
(10, '08:15:00', 1, 4),
(11, '08:30:00', 1, 4),
(12, '08:45:00', 1, 4),
(13, '09:00:00', 1, 4),
(14, '09:15:00', 1, 4),
(15, '09:30:00', 1, 4),
(16, '09:45:00', 1, 4),
(17, '10:00:00', 1, 4),
(18, '10:15:00', 1, 4),
(19, '10:30:00', 1, 4),
(20, '10:45:00', 1, 4),
(21, '11:00:00', 1, 4),
(22, '11:15:00', 1, 4),
(23, '11:30:00', 1, 4),
(24, '11:45:00', 1, 4),
(25, '12:00:00', 2, 4),
(26, '12:15:00', 2, 4),
(27, '12:30:00', 2, 4),
(28, '12:45:00', 2, 4),
(29, '01:00:00', 2, 4),
(30, '01:15:00', 2, 4),
(31, '01:30:00', 2, 4),
(32, '01:45:00', 2, 4),
(33, '02:00:00', 2, 4),
(34, '02:15:00', 2, 4),
(35, '02:30:00', 2, 4),
(36, '02:45:00', 2, 4),
(37, '03:00:00', 2, 4),
(38, '03:15:00', 2, 4),
(39, '03:30:00', 2, 4),
(40, '03:45:00', 2, 4),
(41, '04:00:00', 2, 4),
(42, '04:15:00', 2, 4),
(43, '04:30:00', 2, 4),
(44, '04:45:00', 2, 4),
(45, '05:00:00', 2, 4),
(46, '05:15:00', 2, 4),
(47, '05:30:00', 2, 4),
(48, '05:45:00', 2, 4),
(49, '06:00:00', 2, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario_medico`
--

CREATE TABLE `horario_medico` (
  `id_horario_med` int(11) NOT NULL,
  `doc_medico` bigint(20) NOT NULL,
  `horario` time NOT NULL,
  `id_estado` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_farmacia`
--

CREATE TABLE `inventario_farmacia` (
  `id_inventario` int(11) NOT NULL,
  `id_medicamento` int(11) NOT NULL,
  `nit_farm` bigint(20) NOT NULL,
  `cantidad_inicial` int(20) NOT NULL,
  `cantidad_actual` int(20) NOT NULL DEFAULT 0,
  `fecha_ultima_actualizacion` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario_farmacia`
--

INSERT INTO `inventario_farmacia` (`id_inventario`, `id_medicamento`, `nit_farm`, `cantidad_inicial`, `cantidad_actual`, `fecha_ultima_actualizacion`) VALUES
(1, 1, 800149695, 100, 100, '2025-04-22 13:24:13'),
(2, 1, 800232121, 150, 150, '2025-04-22 13:24:13'),
(3, 1, 816001182, 80, 80, '2025-04-22 13:24:13'),
(4, 1, 860007336, 200, 200, '2025-04-22 13:24:13'),
(5, 1, 860013570, 120, 120, '2025-04-22 13:24:13'),
(6, 1, 900432887, 90, 90, '2025-04-22 13:24:13'),
(7, 1, 900580962, 110, 110, '2025-04-22 13:24:13'),
(8, 2, 800149695, 250, 250, '2025-04-22 13:24:13'),
(9, 2, 800232121, 180, 180, '2025-04-22 13:24:13'),
(10, 2, 816001182, 220, 220, '2025-04-22 13:24:13'),
(11, 2, 860007336, 300, 300, '2025-04-22 13:24:13'),
(12, 2, 860013570, 90, 90, '2025-04-22 13:24:13'),
(13, 2, 900432887, 110, 110, '2025-04-22 13:24:13'),
(14, 2, 900580962, 130, 130, '2025-04-22 13:24:13'),
(15, 3, 800149695, 50, 50, '2025-04-22 13:24:13'),
(16, 3, 800232121, 60, 60, '2025-04-22 13:24:13'),
(17, 3, 816001182, 75, 75, '2025-04-22 13:24:13'),
(18, 3, 860007336, 85, 85, '2025-04-22 13:24:13'),
(19, 3, 860013570, 95, 95, '2025-04-22 13:24:13'),
(20, 3, 900432887, 30, 30, '2025-04-22 13:24:13'),
(21, 3, 900580962, 40, 40, '2025-04-22 13:24:13'),
(22, 4, 800149695, 300, 300, '2025-04-22 13:24:13'),
(23, 4, 800232121, 280, 280, '2025-04-22 13:24:13'),
(24, 4, 816001182, 250, 250, '2025-04-22 13:24:13'),
(25, 4, 860007336, 220, 220, '2025-04-22 13:24:13'),
(26, 4, 860013570, 190, 190, '2025-04-22 13:24:13'),
(27, 4, 900432887, 210, 210, '2025-04-22 13:24:13'),
(28, 4, 900580962, 240, 240, '2025-04-22 13:24:13'),
(29, 5, 800149695, 100, 100, '2025-04-22 13:24:13'),
(30, 5, 800232121, 115, 115, '2025-04-22 13:24:13'),
(31, 5, 816001182, 125, 125, '2025-04-22 13:24:13'),
(32, 5, 860007336, 110, 110, '2025-04-22 13:24:13'),
(33, 5, 860013570, 130, 130, '2025-04-22 13:24:13'),
(34, 5, 900432887, 140, 140, '2025-04-22 13:24:13'),
(35, 5, 900580962, 95, 95, '2025-04-22 13:24:13'),
(36, 6, 800149695, 160, 160, '2025-04-22 13:24:13'),
(37, 6, 800232121, 170, 170, '2025-04-22 13:24:13'),
(38, 6, 816001182, 155, 155, '2025-04-22 13:24:13'),
(39, 6, 860007336, 190, 190, '2025-04-22 13:24:13'),
(40, 6, 860013570, 175, 175, '2025-04-22 13:24:13'),
(41, 6, 900432887, 180, 180, '2025-04-22 13:24:13'),
(42, 6, 900580962, 145, 145, '2025-04-22 13:24:13'),
(43, 7, 800149695, 300, 300, '2025-04-22 13:24:13'),
(44, 7, 800232121, 350, 350, '2025-04-22 13:24:13'),
(45, 7, 816001182, 280, 280, '2025-04-22 13:24:13'),
(46, 7, 860007336, 310, 310, '2025-04-22 13:24:13'),
(47, 7, 860013570, 290, 290, '2025-04-22 13:24:13'),
(48, 7, 900432887, 320, 320, '2025-04-22 13:24:13'),
(49, 7, 900580962, 270, 270, '2025-04-22 13:24:13'),
(50, 8, 800149695, 50, 50, '2025-04-22 13:24:13'),
(51, 8, 800232121, 60, 60, '2025-04-22 13:24:13'),
(52, 8, 816001182, 40, 40, '2025-04-22 13:24:13'),
(53, 8, 860007336, 65, 65, '2025-04-22 13:24:13'),
(54, 8, 860013570, 45, 45, '2025-04-22 13:24:13'),
(55, 8, 900432887, 70, 70, '2025-04-22 13:24:13'),
(56, 8, 900580962, 55, 55, '2025-04-22 13:24:13'),
(57, 9, 800149695, 90, 90, '2025-04-22 13:24:13'),
(58, 9, 800232121, 85, 85, '2025-04-22 13:24:13'),
(59, 9, 816001182, 80, 80, '2025-04-22 13:24:13'),
(60, 9, 860007336, 120, 120, '2025-04-22 13:24:13'),
(61, 9, 860013570, 100, 100, '2025-04-22 13:24:13'),
(62, 9, 900432887, 110, 110, '2025-04-22 13:24:13'),
(63, 9, 900580962, 75, 75, '2025-04-22 13:24:13'),
(64, 10, 800149695, 120, 120, '2025-04-22 13:24:13'),
(65, 10, 800232121, 130, 130, '2025-04-22 13:24:13'),
(66, 10, 816001182, 110, 110, '2025-04-22 13:24:13'),
(67, 10, 860007336, 140, 140, '2025-04-22 13:24:13'),
(68, 10, 860013570, 125, 125, '2025-04-22 13:24:13'),
(69, 10, 900432887, 105, 105, '2025-04-22 13:24:13'),
(70, 10, 900580962, 115, 115, '2025-04-22 13:24:13'),
(71, 11, 800149695, 500, 500, '2025-04-22 13:24:13'),
(72, 11, 800232121, 450, 450, '2025-04-22 13:24:13'),
(73, 11, 816001182, 600, 600, '2025-04-22 13:24:13'),
(74, 11, 860007336, 550, 550, '2025-04-22 13:24:13'),
(75, 11, 860013570, 480, 480, '2025-04-22 13:24:13'),
(76, 11, 900432887, 520, 520, '2025-04-22 13:24:13'),
(77, 11, 900580962, 490, 490, '2025-04-22 13:24:13'),
(78, 12, 800149695, 70, 70, '2025-04-22 13:24:13'),
(79, 12, 800232121, 60, 60, '2025-04-22 13:24:13'),
(80, 12, 816001182, 80, 80, '2025-04-22 13:24:13'),
(81, 12, 860007336, 90, 90, '2025-04-22 13:24:13'),
(82, 12, 860013570, 50, 50, '2025-04-22 13:24:13'),
(83, 12, 900432887, 75, 75, '2025-04-22 13:24:13'),
(84, 12, 900580962, 65, 65, '2025-04-22 13:24:13'),
(85, 13, 800149695, 40, 40, '2025-04-22 13:24:13'),
(86, 13, 800232121, 35, 35, '2025-04-22 13:24:13'),
(87, 13, 816001182, 50, 50, '2025-04-22 13:24:13'),
(88, 13, 860007336, 45, 45, '2025-04-22 13:24:13'),
(89, 13, 860013570, 30, 30, '2025-04-22 13:24:13'),
(90, 13, 900432887, 55, 55, '2025-04-22 13:24:13'),
(91, 13, 900580962, 38, 38, '2025-04-22 13:24:13'),
(92, 14, 800149695, 200, 200, '2025-04-22 13:24:13'),
(93, 14, 800232121, 210, 210, '2025-04-22 13:24:13'),
(94, 14, 816001182, 180, 180, '2025-04-22 13:24:13'),
(95, 14, 860007336, 250, 250, '2025-04-22 13:24:13'),
(96, 14, 860013570, 190, 190, '2025-04-22 13:24:13'),
(97, 14, 900432887, 220, 220, '2025-04-22 13:24:13'),
(98, 14, 900580962, 230, 230, '2025-04-22 13:24:13'),
(99, 15, 800149695, 80, 80, '2025-04-22 13:24:13'),
(100, 15, 800232121, 90, 90, '2025-04-22 13:24:13'),
(101, 15, 816001182, 70, 70, '2025-04-22 13:24:13'),
(102, 15, 860007336, 100, 100, '2025-04-22 13:24:13'),
(103, 15, 860013570, 60, 60, '2025-04-22 13:24:13'),
(104, 15, 900432887, 85, 85, '2025-04-22 13:24:13'),
(105, 15, 900580962, 75, 75, '2025-04-22 13:24:13'),
(197, 16, 800149695, 40, 40, '2025-04-22 13:27:27'),
(198, 16, 800232121, 55, 55, '2025-04-22 13:27:27'),
(199, 16, 816001182, 30, 30, '2025-04-22 13:27:27'),
(200, 16, 860007336, 60, 60, '2025-04-22 13:27:27'),
(201, 16, 860013570, 35, 35, '2025-04-22 13:27:27'),
(202, 16, 900432887, 45, 45, '2025-04-22 13:27:27'),
(203, 16, 900580962, 50, 50, '2025-04-22 13:27:27'),
(204, 17, 800149695, 60, 60, '2025-04-22 13:27:27'),
(205, 17, 800232121, 70, 70, '2025-04-22 13:27:27'),
(206, 17, 816001182, 50, 50, '2025-04-22 13:27:27'),
(207, 17, 860007336, 80, 80, '2025-04-22 13:27:27'),
(208, 17, 860013570, 55, 55, '2025-04-22 13:27:27'),
(209, 17, 900432887, 65, 65, '2025-04-22 13:27:27'),
(210, 17, 900580962, 75, 75, '2025-04-22 13:27:27'),
(211, 18, 800149695, 20, 20, '2025-04-22 13:27:27'),
(212, 18, 800232121, 30, 30, '2025-04-22 13:27:27'),
(213, 18, 816001182, 15, 15, '2025-04-22 13:27:27'),
(214, 18, 860007336, 35, 35, '2025-04-22 13:27:27'),
(215, 18, 860013570, 25, 25, '2025-04-22 13:27:27'),
(216, 18, 900432887, 40, 40, '2025-04-22 13:27:27'),
(217, 18, 900580962, 28, 28, '2025-04-22 13:27:27'),
(218, 19, 800149695, 150, 150, '2025-04-22 13:27:27'),
(219, 19, 800232121, 160, 160, '2025-04-22 13:27:27'),
(220, 19, 816001182, 140, 140, '2025-04-22 13:27:27'),
(221, 19, 860007336, 170, 170, '2025-04-22 13:27:27'),
(222, 19, 860013570, 130, 130, '2025-04-22 13:27:27'),
(223, 19, 900432887, 180, 180, '2025-04-22 13:27:27'),
(224, 19, 900580962, 145, 145, '2025-04-22 13:27:27'),
(225, 20, 800149695, 90, 90, '2025-04-22 13:27:27'),
(226, 20, 800232121, 100, 100, '2025-04-22 13:27:27'),
(227, 20, 816001182, 80, 80, '2025-04-22 13:27:27'),
(228, 20, 860007336, 110, 110, '2025-04-22 13:27:27'),
(229, 20, 860013570, 70, 70, '2025-04-22 13:27:27'),
(230, 20, 900432887, 95, 95, '2025-04-22 13:27:27'),
(231, 20, 900580962, 85, 85, '2025-04-22 13:27:27'),
(232, 21, 800149695, 280, 280, '2025-04-22 13:27:27'),
(233, 21, 800232121, 260, 260, '2025-04-22 13:27:27'),
(234, 21, 816001182, 300, 300, '2025-04-22 13:27:27'),
(235, 21, 860007336, 250, 250, '2025-04-22 13:27:27'),
(236, 21, 860013570, 270, 270, '2025-04-22 13:27:27'),
(237, 21, 900432887, 240, 240, '2025-04-22 13:27:27'),
(238, 21, 900580962, 290, 290, '2025-04-22 13:27:27'),
(239, 22, 800149695, 30, 30, '2025-04-22 13:27:27'),
(240, 22, 800232121, 40, 40, '2025-04-22 13:27:27'),
(241, 22, 816001182, 25, 25, '2025-04-22 13:27:27'),
(242, 22, 860007336, 45, 45, '2025-04-22 13:27:27'),
(243, 22, 860013570, 35, 35, '2025-04-22 13:27:27'),
(244, 22, 900432887, 50, 50, '2025-04-22 13:27:27'),
(245, 22, 900580962, 38, 38, '2025-04-22 13:27:27'),
(246, 23, 800149695, 100, 100, '2025-04-22 13:27:27'),
(247, 23, 800232121, 110, 110, '2025-04-22 13:27:27'),
(248, 23, 816001182, 90, 90, '2025-04-22 13:27:27'),
(249, 23, 860007336, 120, 120, '2025-04-22 13:27:27'),
(250, 23, 860013570, 80, 80, '2025-04-22 13:27:27'),
(251, 23, 900432887, 105, 105, '2025-04-22 13:27:27'),
(252, 23, 900580962, 95, 95, '2025-04-22 13:27:27'),
(253, 24, 800149695, 50, 50, '2025-04-22 13:27:27'),
(254, 24, 800232121, 60, 60, '2025-04-22 13:27:27'),
(255, 24, 816001182, 40, 40, '2025-04-22 13:27:27'),
(256, 24, 860007336, 70, 70, '2025-04-22 13:27:27'),
(257, 24, 860013570, 30, 30, '2025-04-22 13:27:27'),
(258, 24, 900432887, 55, 55, '2025-04-22 13:27:27'),
(259, 24, 900580962, 45, 45, '2025-04-22 13:27:27'),
(260, 25, 800149695, 30, 30, '2025-04-22 13:27:27'),
(261, 25, 800232121, 35, 35, '2025-04-22 13:27:27'),
(262, 25, 816001182, 25, 25, '2025-04-22 13:27:27'),
(263, 25, 860007336, 40, 40, '2025-04-22 13:27:27'),
(264, 25, 860013570, 20, 20, '2025-04-22 13:27:27'),
(265, 25, 900432887, 38, 38, '2025-04-22 13:27:27'),
(266, 25, 900580962, 28, 28, '2025-04-22 13:27:27'),
(267, 26, 800149695, 70, 70, '2025-04-22 13:27:27'),
(268, 26, 800232121, 80, 80, '2025-04-22 13:27:27'),
(269, 26, 816001182, 60, 60, '2025-04-22 13:27:27'),
(270, 26, 860007336, 90, 90, '2025-04-22 13:27:27'),
(271, 26, 860013570, 50, 50, '2025-04-22 13:27:27'),
(272, 26, 900432887, 75, 75, '2025-04-22 13:27:27'),
(273, 26, 900580962, 65, 65, '2025-04-22 13:27:27'),
(274, 27, 800149695, 100, 100, '2025-04-22 13:27:27'),
(275, 27, 800232121, 90, 90, '2025-04-22 13:27:27'),
(276, 27, 816001182, 110, 110, '2025-04-22 13:27:27'),
(277, 27, 860007336, 120, 120, '2025-04-22 13:27:27'),
(278, 27, 860013570, 80, 80, '2025-04-22 13:27:27'),
(279, 27, 900432887, 105, 105, '2025-04-22 13:27:27'),
(280, 27, 900580962, 95, 95, '2025-04-22 13:27:27'),
(281, 28, 800149695, 130, 130, '2025-04-22 13:27:27'),
(282, 28, 800232121, 140, 140, '2025-04-22 13:27:27'),
(283, 28, 816001182, 120, 120, '2025-04-22 13:27:27'),
(284, 28, 860007336, 150, 150, '2025-04-22 13:27:27'),
(285, 28, 860013570, 110, 110, '2025-04-22 13:27:27'),
(286, 28, 900432887, 135, 135, '2025-04-22 13:27:27'),
(287, 28, 900580962, 125, 125, '2025-04-22 13:27:27'),
(288, 29, 800149695, 90, 90, '2025-04-22 13:27:27'),
(289, 29, 800232121, 100, 100, '2025-04-22 13:27:27'),
(290, 29, 816001182, 80, 80, '2025-04-22 13:27:27'),
(291, 29, 860007336, 110, 110, '2025-04-22 13:27:27'),
(292, 29, 860013570, 70, 70, '2025-04-22 13:27:27'),
(293, 29, 900432887, 95, 95, '2025-04-22 13:27:27'),
(294, 29, 900580962, 85, 85, '2025-04-22 13:27:27'),
(295, 30, 800149695, 150, 150, '2025-04-22 13:27:27'),
(296, 30, 800232121, 160, 160, '2025-04-22 13:27:27'),
(297, 30, 816001182, 140, 140, '2025-04-22 13:27:27'),
(298, 30, 860007336, 170, 170, '2025-04-22 13:27:27'),
(299, 30, 860013570, 130, 130, '2025-04-22 13:27:27'),
(300, 30, 900432887, 180, 180, '2025-04-22 13:27:27'),
(301, 30, 900580962, 155, 155, '2025-04-22 13:27:27'),
(302, 31, 800149695, 80, 80, '2025-04-22 13:27:27'),
(303, 31, 800232121, 70, 70, '2025-04-22 13:27:27'),
(304, 31, 816001182, 90, 90, '2025-04-22 13:27:27'),
(305, 31, 860007336, 100, 100, '2025-04-22 13:27:27'),
(306, 31, 860013570, 60, 60, '2025-04-22 13:27:27'),
(307, 31, 900432887, 85, 85, '2025-04-22 13:27:27'),
(308, 31, 900580962, 75, 75, '2025-04-22 13:27:27'),
(309, 32, 800149695, 50, 50, '2025-04-22 13:27:27'),
(310, 32, 800232121, 55, 55, '2025-04-22 13:27:27'),
(311, 32, 816001182, 45, 45, '2025-04-22 13:27:27'),
(312, 32, 860007336, 60, 60, '2025-04-22 13:27:27'),
(313, 32, 860013570, 40, 40, '2025-04-22 13:27:27'),
(314, 32, 900432887, 65, 65, '2025-04-22 13:27:27'),
(315, 32, 900580962, 48, 48, '2025-04-22 13:27:27'),
(316, 33, 800149695, 20, 20, '2025-04-22 13:27:27'),
(317, 33, 800232121, 25, 25, '2025-04-22 13:27:27'),
(318, 33, 816001182, 15, 15, '2025-04-22 13:27:27'),
(319, 33, 860007336, 30, 30, '2025-04-22 13:27:27'),
(320, 33, 860013570, 18, 18, '2025-04-22 13:27:27'),
(321, 33, 900432887, 28, 28, '2025-04-22 13:27:27'),
(322, 33, 900580962, 22, 22, '2025-04-22 13:27:27'),
(323, 34, 800149695, 100, 100, '2025-04-22 13:27:27'),
(324, 34, 800232121, 100, 100, '2025-04-22 13:27:27'),
(325, 34, 816001182, 100, 100, '2025-04-22 13:27:27'),
(326, 34, 860007336, 150, 150, '2025-04-22 13:27:27'),
(327, 34, 860013570, 150, 150, '2025-04-22 13:27:27'),
(328, 34, 900432887, 120, 120, '2025-04-22 13:27:27'),
(329, 34, 900580962, 120, 120, '2025-04-22 13:27:27'),
(330, 35, 800149695, 200, 200, '2025-04-22 13:27:27'),
(331, 35, 800232121, 180, 180, '2025-04-22 13:27:27'),
(332, 35, 816001182, 220, 220, '2025-04-22 13:27:27'),
(333, 35, 860007336, 210, 210, '2025-04-22 13:27:27'),
(334, 35, 860013570, 190, 190, '2025-04-22 13:27:27'),
(335, 35, 900432887, 230, 230, '2025-04-22 13:27:27'),
(336, 35, 900580962, 170, 170, '2025-04-22 13:27:27'),
(337, 36, 800149695, 70, 70, '2025-04-22 13:27:27'),
(338, 36, 800232121, 80, 80, '2025-04-22 13:27:27'),
(339, 36, 816001182, 60, 60, '2025-04-22 13:27:27'),
(340, 36, 860007336, 90, 90, '2025-04-22 13:27:27'),
(341, 36, 860013570, 50, 50, '2025-04-22 13:27:27'),
(342, 36, 900432887, 75, 75, '2025-04-22 13:27:27'),
(343, 36, 900580962, 65, 65, '2025-04-22 13:27:27'),
(344, 37, 800149695, 40, 40, '2025-04-22 13:27:27'),
(345, 37, 800232121, 50, 50, '2025-04-22 13:27:27'),
(346, 37, 816001182, 30, 30, '2025-04-22 13:27:27'),
(347, 37, 860007336, 55, 55, '2025-04-22 13:27:27'),
(348, 37, 860013570, 35, 35, '2025-04-22 13:27:27'),
(349, 37, 900432887, 45, 45, '2025-04-22 13:27:27'),
(350, 37, 900580962, 38, 38, '2025-04-22 13:27:27');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ips`
--

CREATE TABLE `ips` (
  `Nit_IPS` bigint(20) NOT NULL,
  `nom_IPS` varchar(100) NOT NULL,
  `direc_IPS` varchar(150) NOT NULL,
  `nom_gerente` varchar(50) NOT NULL,
  `tel_IPS` varchar(20) NOT NULL,
  `correo_IPS` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ips`
--

INSERT INTO `ips` (`Nit_IPS`, `nom_IPS`, `direc_IPS`, `nom_gerente`, `tel_IPS`, `correo_IPS`) VALUES
(89140038, 'E.S.E. HOSPITAL SANTA MONICA (SANTA ROSA DE CABAL)', 'Carrera 14 No. 29-30', 'Sr. Gabriel Antonio Muñoz', '3658088', 'calidadhospitalsantamonicasantarosadecabal.co - secretaria@hospitalsantarosadecaba'),
(800001704, 'E.S.E. HOSPITAL MARIO GAITAN YANGUAS DE SOACHA', 'CRA 12 NO. 13-98', 'Dra. Alexandra González Moreno', '7309230 ext. 2001', 'gerencia@esesohacha.gov.co'),
(800007021, 'Hospital Departamental de Granada - Empresa Social del Meta', 'Calle 15 Carrera 2 y 4', 'Dr. Nelson Eduardo López', '6587800', 'gerencia@hospitalgranada.gov.co'),
(800008138, 'E.S.E. HOSPITAL DEPARTAMENTAL UNIVERSITARIO DEL QUINDIO SAN JUAN DE DIOS (ARMENIA)', 'AV BOLIVAR CALLE 1N', 'Sr. Jairo López Marín', '737-10-10 Ext 132', 'contacto@hospitalsanjuandearmenia.gov.co'),
(800017596, 'ESE HOSPITAL DEPARTAMENTAL UNIVERSITARIO SANTA SOFIA DE CALDAS', 'CALLE 47 NO 27A-02 BARRIO ASTURIAS', 'Dra. Gloria Patricia Yepes', '8879200 - 0180009106', 'santasofia@sescaldas.gov.co'),
(800071724, 'HOSPITAL SANTO DOMINGO EMPRESA SOCIAL DEL ESTADO (CASABIANCA)', 'Calle 2 No. 5-07', 'Sr. Oscar Fabián Rodríguez', '2548584', 'hospitalcasabianca@hotmail.com'),
(800073550, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL SANTO TOMAS (VILLANUEVA)', 'CARRERA 8 No. 19 - 27', 'Sr. Alberto José Fuentes', '(857)770838', 'hostomas@hotmail.com'),
(800074995, 'E.S.E. HOSPITAL MENTAL DE ANTIOQUIA \"MARIA UPEGUI\" HOMO', 'CALLE 38 No 55-310 BARRIO SANTA ANA', 'Sr. Andrés Felipe Montoya', '4482030', 'gerencia@homo.gov.co'),
(800101022, 'E.S.E. HOSPITAL SANTA TERESITA DE JESUS (BARRANCAS)', 'CALLE 11 CARRERA 9 VIA A LA PLATA', 'Sra. Paola Andrea Bruges', '7748026', 'hospitalbar@yahoo.es'),
(800101945, 'ESE HOSPITAL LOCAL CURUMANI CRISTIAN MORENO PALLARES', 'CALLE 2DA CARRERA 17 ESQUINA', 'Sr. Carlos Mario Martínez', '3135256885', 'gerencia@hoscrismopa.gov.co'),
(800102438, 'HOSPITAL UNIVERSITARIO FERNANDO TROCONIS DE OLIVERA (SANTA MARTA)', 'Av. Libertador No. 30-100', 'Dr. Alejandro José Dávila', '4380000', 'gerencia@huhft.gov.co'),
(800103480, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL SANTA ANA NIVEL I DEL MUNICIPIO DE FALAN', 'Calle 4 # 12-40', 'Ing. Patricia Isabel Lozano', '0105582120 / 3208996', 'gerencia@hospitalsantaanafalan.gov.co'),
(800124275, 'E.S.E. HOSPITAL SAN RAFAEL DE FACATATIVA', 'CL 1 No.5-00', 'Sra. Ana Lucía López Pinzón', '(091) 8422800', 'gerencia@hospitalfacatativa.gov.co'),
(800125697, 'E.S.E. HOSPITAL NELSON RESTREPO MARTINEZ (ARMERO GUAYABAL)', 'Kra 6 calle 8 esquina', 'Sr. Miguel Ángel Perdomo', '2830123 / 2812200', 'administracion@esenhospitalarmenia.gov.co'),
(800130825, 'ESE HOSPITAL SAN CRISTOBAL DE CIENAGA', 'Calle 20 No. 20 Barrio el Progreso', 'Sra. Elvira Rosa Campo', '0356399606', 'gerencia@esehospitalsancristobalcienaga.gov.co'),
(800146767, 'ESE SUROCCIDENTE', 'Calle 5 N° 9-55 BARRIO EL CAMPIN - EL BORDO CA', 'Sr. Juan Carlos López Muñoz', '6262896', 'esesuroccidentemayor@gmail.com'),
(800155000, 'HOSPITAL SAN AGUSTIN DE PUERTO MERIZALDE', 'CORREGIMIENTO DE PUERTO MERIZALDE', 'Sra. Beatriz Eugenia Solís', '315556753', 'gerencia@hospitalsanagustinm.gov.co'),
(800162807, 'CLINICA DE LA COSTA REGIONAL CARIBE', 'AVENIDA CIRCUNVALAR NUMERO 45-124', 'Sra. Diana Carolina Perea', '3856600 EXT 8201', 'direccion.clinica-seccional@clcosta.com'),
(800165765, 'ESE HOSPITAL NUESTRA SEÑORA DEL PERPETUO SOCORRO (URIBIA)', 'TRANSVERSAL 8 N° 9-45', 'Dra. Fabiola Isabel Iguarán', '5717532', 'atencionalcliente@esehnps.gov.co'),
(800225667, 'E.S.E. HOSPITAL SAN JOSE DE ATACÓ', 'Kra 6#2-99', 'Dra. Sandra Lorena Fierro', '012225899', 'HMPA@yahoo.com'),
(801000235, 'REDSALUD ARMENIA ESE', 'Av Centenario Cr 6 Universidad del Quindío', 'Dra. Carolina Arias Vásquez', '05808', 'gerencia@redsaludarmenia.gov.co'),
(802001607, 'E.S.E. HOSPITAL DEPARTAMENTAL DE SABANALARGA', 'CALLE 22 Nº 1 - 25', 'Sr. Esteban David Díaz', '8781402', 'ESEHOSPITALDESABANALARGAGRANDE@HOTMAIL.COM'),
(802013002, 'ESE HOSPITAL MATERNO INFANTIL CIUDADELA METROPOLITANO', 'KR 6A No 45C-06 BARRIO COSTA HERMOSA', 'Dra. Sofia Isabel Martínez', '3759400', 'hospitalmaternoinfantil@hotmail.com'),
(804004530, 'EMPRESA SOCIAL DEL ESTADO INSTITUTO DE SALUD DE BUCARAMANGA ISABU', 'CALLE 45 N° 15-56', 'Sra. Martha Lucía Reyes', '6970000', 'gerencia@isabu.gov.co'),
(804011563, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL PSIQUIATRICO SAN CAMILO (BUCARAMANGA)', 'Carrera 23 N° 30A - 74', 'Sra. Beatriz Helena Mantilla', '6873400', 'gerencia@psiquiatricosancamilo.gov.co'),
(806001909, 'ESE HOSPITAL SANTA ROSA DE LIMA', 'BARRIO ABAJO', 'Sr. Rafael Antonio Blanco', '3012611932', 'hospitalsantarosalima@hotmail.com'),
(806007301, 'E.S.E. HOSPITAL LOCAL DE MAGANGUE', 'Avenida Colombia Nº10-145', 'Sr. Manuel José Petro', '300822404 - 31268473', 'esehospitaldivinamisericordiam@hotmail.com'),
(806007689, 'ESE HOSPITAL LOCAL SAN PABLO', 'CARRERA 18 CON CALLE 7 ESQUINA', 'Dra. Isabel Cristina Muñoz', '314405153', 'contactenos@esehospitalsanpablodebolivar.gov.co'),
(807004552, 'EMPRESA SOCIAL DEL ESTADO IMSALUD (CUCUTA)', 'Avenida 6A # 0-23 Barrio Blanco', 'Sr. Pedro Luis Fonseca', '5840434', 'info@imsalud.gov.co'),
(807004938, 'ESE HOSPITAL REGIONAL NORTE (TIBÚ)', 'CALLE 5 N° 9-01 BARRIO VIÑA DEL ROSARIO', 'Ing. Camilo Andrés Suárez', '5663047 - 5663240 - ', 'secretaria.gerencia@eseregionalnorte.gov.co'),
(807008945, 'E.S.E. HOSPITAL REGIONAL NOROCCIDENTAL (OCAÑA)', 'Barrio Kennedy', 'Dr. Mauricio Alberto Cadena', '095 3566706-5690116-', 'hospitalregionalocana@senderogroupmail.com'),
(808011197, 'CENTRO DE PRESTACION DE SERVICIOS DE SALUD INDIGENA SALUD YAGUAR (CHAPARRAL)', 'CRA 8 No. 4-19- 23 BARRIO SAN JUAN BAUTISTA', 'Sr. Edgar Iván Sánchez', '2461139', 'esasaludyaguar@gmail.com'),
(808011882, 'CENTRO DE SALUD COELLO O.S.E.', 'Calle 4 # 1 - 07', 'Dra. Carolina Andrea Bonilla', '2863966', 'Coelloese@esecco-tolima.gov.co'),
(812000410, 'E.S.E. HOSPITAL SAN JOSE DE TADÓ', 'Calle 4 Nro19-1', 'Dra. Olga Lucía Rivas', '946795137', 'hospitaltado@yahoo.es'),
(812001332, 'E.S.E. HOSPITAL SAN JUAN DE SAHAGUN', 'KL 33-64', 'Sra. Ema del Carmen Díaz', '7777774', 'esehospitalsanjuans@gmail.com'),
(812003444, 'EMPRESA SOCIAL DEL ESTADO CAMU DE MONTERIA', 'Calle 44 No. 8 - 10 B - Barrio La Floresta', 'Sr. Cristóbal José Fortich', '7828098', 'gerencia@esecamudelsinu.gov.co'),
(813000240, 'EMPRESA SOCIAL DEL ESTADO MARIA AUXILIADORA DE GARZON', 'CARRERA 9 No. 3 - 39', 'Sr. Leonardo Valero', '8333599', 'esemariag@hotmail.com'),
(813001502, 'HOSPITAL DEPARTAMENTAL SAN VICENTE DE PAUL (GARZON)', 'CALLE 7 N 1-25 Y CALLE 7 1-49', 'Dra. Íngrid Marcela Gaviria', '8332570', 'calidadhospitalsanvicentedepaul@gmail.com'),
(814000620, 'E.S.E. CENTRO DE SALUD VICTORIA SANTA CRUZ (TUMACO)', 'CL 11A # 9 - 38 BARRIO PANAMERICANO', 'Sra. Flor Ángela Hernández', '321491143', 'sub_tumacoese@sesnar.gov.co'),
(814002238, 'E.S.E. HOSPITAL CIVIL DE IPIALES', 'KR 6A # 24-39', 'Sr. Iván Mauricio Coral', '742351', 'gerencia@esenhospitalcivilipiales.gov.co'),
(816005005, 'EMPRESA SOCIAL DEL ESTADO SALUD PEREIRA', 'CRA 7 BIS #41-35 ED CENTRO', 'Dr. Francisco Javier Valencia', '3252152 EXT 526', 'corrseo@saludpereira.gov.co'),
(818001301, 'NUEVA EMPRESA SOCIAL DEL ESTADO HOSPITAL SAN FRANCISCO DE ASIS (ISTMINA)', 'CR 1 LOTESN KENNEDY', 'Dr. Anuar Jair Catillo', '6715050', 'esediariosegundo@gmail.com'),
(819001493, 'E.S.E HOSPITAL FRAY LUIS DE LEON (PLATO)', 'CALLE 7A VIA LOS CONTENEDORES', 'Dra. Luisa Fernanda Cotes', '4652003 3154060926', 'gerencia@hospitalplato.gov.co'),
(820000831, 'HOSPITAL RUBEN CRUZ VELEZ (TULUÁ)', 'Calle 26 No. 39-145', 'Dr. Felipe José Tinoco', '(032)3333392 ext 101', 'gerencia@hospitalrubencruzvelez.gov.co'),
(820000895, 'ESE HOSPITAL REGIONAL DE CHIQUINQUIRA', 'CARRERA 7 18 - 60', 'Dra. Martha Lucía Solano', '7261152', 'hospitalchiquinquira@yahoo.es'),
(820000920, 'EMPRESA SOCIAL DEL ESTADO CENTRO DE SALUD DE SOGAMOSO', 'CARRERA 15 6 -126', 'Sr. Daniel Alejandro Cruz', '7702109', 'ese@saludsogamoso.gov.co'),
(822000051, 'EMPRESA SOCIAL DEL ESTADO SOLUCION SALUD (VILLAVICENCIO)', 'CARRERA 32 N 40- 28 BARRIO ALTO', 'Sr. Jhon Jairo Pulido', '6614100', 'saludvillavicencio@esevillavicencio.gov.co'),
(822000450, 'HOSPITAL DEPARTAMENTAL DE VILLAVICENCIO E.S.E.', 'CARRERA 42 No. 11C - 03 BARRIO ALTO', 'Dra. Maryury Díaz Céspedes', '6817901 ext 111', 'gerencia.secretaria@hdv.gov.co'),
(822002235, 'HOSPITAL DEL SARARE ESE', 'Calle 30 N° 10A - 42', 'Sr. Jorge Luis Bernal Soto', '7-882835', 'correspondencia@esehospitaldelsarare.gov.co'),
(823000000, 'ESE HOSPITAL MALVINAS HECTOR OROZCO OROZCO', 'Cra 1 No. 20-46 AVENIDA PUERTO RICO', 'Sr. Julián Andrés Perdomo', '454462100', 'facturacion@hospitalmalvinas.gov.co'),
(823000904, 'E.S.E. HOSPITAL LOCAL SAN FRANCISCO DE ASIS (SINCE)', 'CALLE 14 B-BARRIO LA ESMERALDA', 'Sr. Antonio José Vergara', '2831299', 'esefranciscosince@hotmail.com'),
(823000956, 'HOSPITAL LOCAL SANTA ANA DE TOLUVIEJO E.S.E', 'TRANSVERSAL 4A N 3-49-PLAZA PRINCIPAL TOLUVIEJO', 'Sr. Rafael David Martínez', '3014998223', 'hstoluviejo@hotmail.com'),
(823001636, 'ESE HOSPITAL NUESTRA SEÑORA DE LAS MERCEDES DE COROZAL E.S.E.', 'CALLE 22 # 18-30', 'Sra. Patricia Helena Pérez', '2457738', 'esecorozal@hotmail.com'),
(823004903, 'E.S.E. HOSPITAL SAN RAFAEL DE LETICIA', 'CARRERA 10 NO. 13-78 Leticia (Amazonas)', 'Dra. Elena Rojas Vidal', '3203040903', 'gerencia@hospitalsanrafael-leticia-amazonas.gov.co'),
(825005295, 'HOSPITAL SAN RAFAEL DE ALBANIA', 'CARRERA 6 NO. 3-05', 'Dr. Nicolás Deluque Zuleta', '777 45 96', 'contactenos@hospitalsanrafaeldealbania-laguajira.gov.co'),
(829001246, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL REGIONAL DEL MAGDALENA MEDIO (BARRANCABERMEJA)', 'CALLE 52 N° 28A - 06 BARRIO COLOMBIA', 'Dr. José Antonio García', '6010105', 'secretariagerencia@hrmm.gov.co'),
(830004002, 'HOSPITAL MILITAR CENTRAL', 'TV 3 # 49-00', 'General Luis Carlos Peña', '3486868', 'subdireccion.cientifica@hospitalmilitar.gov.co'),
(834000125, 'DIRECCION DE SANIDAD POLICIA NACIONAL', 'CRA 5 N° 7-50', 'Coronel Javier Ignacio Soto', '6854877', 'ara.hosan@policia.gov.co'),
(834001492, 'HOSPITAL SAN VICENTE DE ARAUCA', 'Cra 15 # 18A', 'Dr. Ricardo José Páez', '(097)-8852030', 'gerencia@hospitalsanvicente.gov.co'),
(835000972, 'HOSPITAL LUIS ABLANQUE DE LA PLATA EMPRESA SOCIAL DEL ESTADO', 'CRA 4 # 2-98', 'Dr. Fernando Aguirre Castillo', '2427441', 'gerenciahosp1.2015@gmail.com'),
(844001287, 'HOSPITAL LOCAL DE TAURAMENA', 'KR 13A # 8-63', 'Sr. Oscar Javier Morales', '012 54 10892', 'esehospitauraltauramenahospital-tauramena-casanare.gov.co'),
(844002235, 'ESE HOSPITAL SALUD YOPAL', 'Cra. 15 N° 13-40', 'Dr. Edwin Barrera Rodríguez', '098 6324891', 'contactenos@esehospitalyopal-casanare.gov.co'),
(844004197, 'RED SALUD CASANARE E.S.E.', 'Calle 28 N° 20 - 75 YOPAL CASANARE', 'Sra. Victoria Eugenia Hernández', '57 7668 024818', 'contactenos@redsaludcasanare.gov.co'),
(890001038, 'ESE HOSPITAL LA MISERICORDIA (CALARCA)', 'CARRERA 26 No. 39-13', 'Sr. Esteban Rojas Gil', '967483722', 'calidad@hospitallamisericordiacalarca.gov.co'),
(890001824, 'HOSPITAL SAN VICENTE DE PAUL (MONTENEGRO)', 'CARRERA 4 No. 12-65', 'Sra. Diana Patricia Morales', '8866654', 'santamariaeseccm@hotmail.com'),
(890100025, 'ESE HOSPITAL UNIVERSITARIO CARI', 'CALLE 58 Nº 54-163 LA CORDIALIDAD', 'Dr. Alberto Mario Ramírez', '3770000 EXT. 131', 'hospitaljuandedios@hotmail.com'),
(890204243, 'HOSPITAL UNIVERSITARIO DE SANTANDER (BUCARAMANGA)', 'CARRERA 33 # 28 - 126', 'Dr. Edgar Julián Niño Carrillo', '6978111 - 8965111', 'gerencia@hospitaluniversitariodesantander.gov.co'),
(890300195, 'HOSPITAL PSIQUIATRICO UNIVERSITARIO DEL VALLE E.S.E. (CALI)', 'CALLE 5 No. 80-00', 'Sra. Lorena Patricia Botero', '5132300', 'ventanillaunica@hospitalpsiquiatrico.gov.co'),
(890305280, 'HOSPITAL UNIVERSITARIO DEL VALLE \'EVARISTO GARCIA\' E.S.E. (CALI)', 'CL 5B1 # 36 - 08', 'Dra. Clara Eugenia Quintero', '6206000 EXT 1010', 'gerencia@huv.gov.co'),
(890480001, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL UNIVERSITARIO DEL CARIBE', 'ZARAGOCILLA', 'Dra. Carmen Alicia López', '6724240', 'gerenciahuec@hotmail.com'),
(890500810, 'E.S.E. HOSPITAL UNIVERSITARIO ERASMO MEOZ (CUCUTA)', 'AVENIDA 11E No. 5AN-71 GUAPIMARAL', 'Dra. Angela María Cáceres', '5746888', 'huem@huem.gov.co'),
(890680001, 'E.S.E. HOSPITAL SAN RAFAEL DE GIRARDOT', 'CALLE 19 NO. 19-97', 'Sr. Manuel Esteban Sarmiento', '8339212 - 0180004190', 'hospitalsanrafaelgirardot@cundinamarca.gov.co'),
(890680125, 'E.S.E. HOSPITAL SAN RAFAEL DE FUSAGASUGA', 'KR 25 # 12-82 BARRIO SAN MATEO', 'Dr. Andrés Mauricio González', '8730000102', 'gerencia@hospitalfusagasuga.gov.co'),
(890700316, 'HOSPITAL NUESTRA SEÑORA DE LOURDES E.S.E. (CAJAMARCA)', 'CRA. 5 N° 6-54', 'Sr. Hector William Restrepo', '2140362/3006', 'hospitalatacama@hotmail.com'),
(890701810, 'HOSPITAL SAN RAFAEL - Empresa Social del Estado (ESPINAL)', 'KRA 9 No. 6 - 29 Barrio San Rafael', 'Dr. Andrés Fabián Hurtado', '2482018 / 2480153', 'hospitalsanrafaelesp@yahoo.com'),
(890702098, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL SANTA LUCIA (CAJAMARCA)', 'ORA 3/4 S.A.', 'Sra. Juliana Andrea Ortiz', '2870008 / 3104387778', 'sesgsncial@hospitalsantalucia-cajamarca.gov.co'),
(890702242, 'HOSPITAL NUESTRA SEÑORA DEL CARMEN DEL ESTADO (CARMEN DE APICALÁ)', 'Calle Real, No. 5-07', 'Dra. Eliana Marcela Pulido', '2824767/75 49824', 'hospitalcarmendeapicala@hotmail.com'),
(890702361, 'HOSPITAL FEDERICO LLERAS ACOSTA ESE EMPRESA SOCIAL DEL ESTADO (IBAGUE)', 'KRA 1 VIA LA VIGIA HOPITAL', 'Dr. Luis Eduardo González', '0120834786 / 2477751', 'federicoquehablenbien@hotmail.com'),
(890702408, 'E.S.E. HOSPITAL SAN ISIDRO (ALVARADO)', 'CALLE 4 No. 4-52', 'Dra. Claudia Patricia Sánchez', '8216172/3212011311', 'esealvaradotolima@gmail.com'),
(890702446, 'E.S.E. HOSPITAL SAN ROQUE (ALPUJARRA)', 'CRA 4 No. 4-52', 'Sr. Luis Carlos Herrera', '317444398', 'esenhospitalalpujarra@hotmail.com'),
(890702488, 'E.S.E. SAN ANTONIO (AMBALEMA)', 'Calle 6 No. 4-59', 'Dra. Mónica Alejandra Ríos', '2858599', 'esehospitalsanantoniodeambalema@gmail.com'),
(890704459, 'HOSPITAL SAN JUAN BAUTISTA ESE (CHAPARRAL)', 'Calle 11 ENTRE CARRERA 8 Y 9', 'Dra. Sara Marleny Campos', '88246233', 'gerencia@hospitalsanjuabautista.co'),
(890704480, 'HOSPITAL MARIA INMACULADA DEL ESTADO NIVEL I (CORAMA - RIOBLANCO)', 'CR5 N° 10-45', 'Sr. Luis Ernesto Ávila', '2778014', 'hospitalcoromatolima@yahoo.com'),
(890705560, 'HOSPITAL SAN RAFAEL EMPRESA SOCIAL DEL ESTADO (DOLORES)', 'CRA 4/5-92', 'Sra. Paola Andrea Guzmán', '3175339 / 314-877887', 'hospitaldolorestolima@gmail.com'),
(890800105, 'ESE HOSPITAL SAN VICENTE DE PAUL (ANSERMA)', 'Carrera 5 CALLE 16 Y 17', 'Sr. Rodrigo Alberto Londoño', '0668-514348 - 514740', 'gerencia@hospitaldeaguadas.gov.co'),
(890803396, 'HOSPITAL GENERAL SAN ISIDRO EMPRESA SOCIAL DEL ESTADO', 'VEREDA LA PALMA', 'Sr. Miguel Ángel Castaño', '8714206 - 8714206-31', 'gerencia@sansidromanzales.gov.co'),
(890904545, 'HOSPITAL GENERAL DE MEDELLIN LUZ CASTRO DE GUTIERREZ EMPRESA SOCIAL DEL ESTADO', 'Cra. 48 No. 32-102 Medellín - Antioquia - Colombia', 'Dr. Carlos Alberto Vélez', '4447702', 'total@hgm.gov.co'),
(890980496, 'EMPRESA SOCIAL DEL ESTADO METROSALUD', 'CL 44 # 50-27 ED SACATIN', 'Sra. Luisa Fernanda Ríos', '3807505', 'gerencia@metrosalud.gov.co'),
(890985703, 'HOSPITAL MARCO FIDEL SUAREZ', 'CLL 44 40B-80', 'Dra. Patricia Elena Gómez', '4547500', 'gerencia@hmfs.gov.co'),
(891000799, 'E.S.E. HOSPITAL SAN JERONIMO DE MONTERIA', 'CALLE 22 - 50', 'Dr. Rubén Darío Trejos', '7947699 Ext 101, 115', 'gerencia@hospitalsanjeronimo.gov.co'),
(891090015, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL SANDIEGO DE CERETE', 'CRA 20 CALLE 8 BARRIO SANTA TERESA', 'Dra. Ana María Hoyos', '7746272', 'esehospitalsandiego@yahoo.es'),
(891180011, 'E.S.E. HOSPITAL FABIO JARAMILLO LONDOÑO', 'Diagonal 20 N° 7 -29', 'Sra. Consuelo Díaz Romero', '4368464', 'calidadseguridad@hmijil.gov.co'),
(891180065, 'HOSPITAL UNIVERSITARIO HERNANDO MONCALEANO PERDOMO DE NEIVA', 'CALLE 9A # 15 - 25', 'Dr. Jesús Antonio Castro Vargas', '8715907 ext 1999', 'gerencia.eihn@huhmp.gov.co'),
(891180086, 'E.S.E. HOSPITAL DEPARTAMENTAL MARIA INMACULADA', 'Avenida Circunvalar', 'Dra. Sandra Milena Urrea', '4354146 - 4345214 - ', 'secgerencia@hospitalmariainmaculada.gov.co'),
(891180117, 'E.S.E. HOSPITAL DEPARTAMENTAL SAN ANTONIO DE PADUA (LA PLATA)', 'Carrera 2E N. 11-17', 'Sra. Gladys Durán Borrero', '088370149 / 08837101', 'gerencia@esehospitaldelaplata.gov.co'),
(891300141, 'HOSPITAL DIVINO NIÑO (BUGA)', 'Avenida circunvalar numero 9-13', 'Sr. Hernando Antonio Prado', '2372264 - 2272265', 'calidad@hospitaldivinonio.gov.co'),
(891380100, 'HOSPITAL DEPARTAMENTAL MARIO CORREA RENGIFO EMPRESA SOCIAL DEL ESTADO (CALI)', 'CALLE 70 No. 78-35', 'Dr. Christian Andrés Terán', '5183020 ext 218, 200', 'gerencia@hospitalmariocorrearengifo.gov.co calidad@hospitalmariocorrea.gov.co'),
(891401400, 'ESE HOSPITAL MENTAL UNIVERSITARIO DE RISARALDA HOMERO CRIOLLO MARIÑO (PEREIRA)', 'KM 10 VIA CERRITOS', 'Ing. Natalia Andrea Pineda', '363704', 'gerencia.mental.risaralda@gmail.com'),
(891402124, 'HOSPITAL UNIVERSITARIO SAN JORGE (PEREIRA)', 'AV JUAN B GUTIERREZ NO 24 - 68', 'Dra. María Isabel Giraldo', '3296745', 'gerencia@husj.gov.co'),
(891500176, 'HOSPITAL UNIVERSITARIO SAN JOSE DE POPAYAN E.S.E.', 'KR 6 N° 10 N - 142 La Estancia', 'Dra. Derlin Delgado Rodríguez', '018000920409 EXT 101', 'gerencia@husjpopayan.gov.co'),
(891501104, 'EMPRESA SOCIAL DEL ESTADO DEL NORTE 2', 'CL 10 #5 ESQUINA', 'Sra. María Elena Sandoval', '57363611', 'esenorte2@gmail.com'),
(891600006, 'HOSPITAL SAN FRANCISCO DE ASIS', 'KILOMETRO 5 VIA ALA MINA', 'Sr. Luis Eduardo Mena', '6780112 - 6724222', 'gerenciaesehospital@hospitalsanfranciscodeasis-choco.gov.co'),
(891600080, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL SAN ROQUE (EL CARMEN DE ATRATO)', 'CARRERA 4 CALLE 18 ESQUINA', 'Sra. Marina Córdoba Valencia', '3117420966', 'hospitalsanroque2017@hotmail.com'),
(891780008, 'ESE HOSPITAL SAN RAFAEL (FUNDACION)', 'Calle 8 N° 16-27', 'Ing. Mario Alberto Pinedo', '4140124', 'gerencia@esehospitalsanrafaeldefundacion.gov.co'),
(891800519, 'ESE HOSPITAL SAN RAFAEL DE TUNJA', 'CALLE 16 Nº 10-50', 'Dr. Germán Augusto Silva', '7405630', 'gerencia@hospitalsanrafaeltunja.gov.co'),
(891801636, 'ESE HOSPITAL REGIONAL DE DUITAMA', 'KR 19 # 12D - 40', 'Sra. Clara Inés Vargas', '9874834', 'gerencia.hrd@gmail.com'),
(891855028, 'HOSPITAL REGIONAL DE LA ORINOQUIA E.S.E.', 'Calle 15N° 35-96 MANZANA CIUDADES A ECOLOGICO', 'Dra. Arledy Milena Alvarado', '634 4669', 'ventanillaunica@horo.gov.co'),
(892000000, 'EMPRESA SOCIAL DEL ESTADO DEPARTAMENTAL MORENO Y CLAVIJO', 'Calle 20 Nº 41 Edificio Los Angeles', 'Sra. Mónica Liliana Castillo', '097 8857935', 'gerencia@esemorenoyclavijo.gov.co'),
(892000520, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL LOCAL DE SAN MARTIN DE LOS LLANOS', 'CARRERA 7 No. 4-33', 'Sra. Carmen Sofía Rey', '098-6486333', 'administracion@hospitalsanmartinmeta.gov.co'),
(892115007, 'HOSPITAL NUESTRA SEÑORA DE LOS REMEDIOS (RIOHACHA)', 'CALLE 11A No. 11-10', 'Dra. Mariangélica Martínez Camacho', '7290718', 'gerencia@hnsr.gov.co'),
(892115021, 'E.S.E. HOSPITAL SAN JOSE (MAICAO)', 'CR 14 # 11-05', 'Sr. Juan Carlos Mojica Gómez', '7250611', 'gerencia@esehospitalsanjosemaicao.gov.co'),
(892200003, 'HOSPITAL UNIVERSITARIO DE SINCELEJO E.S.E.', 'KR 26 # 16B-100', 'Dr. Jorge Arturo Osorno', '2821869', 'contactenos@hospitaluniversitariosincelejo.gov.co'),
(892300019, 'HOSPITAL ROSARIO PUMAREJO DE LOPEZ - EMPRESA SOCIAL DEL ESTADO', 'Calle 16 Cra. N° 17-141', 'Sra. Jackeline Henríquez Pérez', '095 - 5712309', 'contactenos@hrplopez.gov.co'),
(892300028, 'HOSPITAL EDUARDO ARREDONDO DAZA', 'CALLE 44 N° 10-60', 'Dr. Miguel Ángel Soto García', '5780209 - 5778802', 'hosedodaza@hotmail.com'),
(892400000, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL DEPARTAMENTAL DE SAN ANDRES PROVIDENCIA Y SANTA CATALINA', 'Vía san Luis Sector Bahia Hooker', 'Sr. Kenneth Peterson Bryan', '3000748262', 'planeacion@esehospitaldepartamental.gov.co'),
(892400017, 'HOSPITAL SAN ANDRES (CHIRIGUANA)', 'CRA 6 N. 63', 'Dra. Yenny Fernanda Lahoz', '5280887', 'hospitalsanandreschiriguana@gov.co'),
(899999030, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL UNIVERSITARIO DE LA SAMARITANA', 'Cra. 8 No. 0-55 SUR', 'Sra. Beatriz Elena Mora', '200001-2001', 'gerencia@hus.org.co'),
(899999034, 'INSTITUTO NACIONAL DE CANCEROLOGIA', 'Cl 9 Nº 1-85 SUR', 'Dr. Hernán Darío Cortés', '091337587', 'gerencia@cancer.gov.co'),
(900005515, 'EMPRESA SOCIAL DEL ESTADO E.S.E SALUD DORADA', 'Calle 2 Bario Santander', 'Ing. Liliana María Osorio', '05785950 - 315380213', 'gerencia@esesaludorada-ladorada-caldas.gov.co'),
(900012855, 'FUNDACION HOSPITAL SAN PEDRO (PASTO)', 'AV LA ESPERANZA', 'Dr. Oscar Fernando Rojas Arturo', '3184838774', 'gerentesanpedro@outlook.com'),
(900012871, 'EMPRESA SOCIAL DEL ESTADO PASTO SALUD ESE', 'Carrera 20 No 19B-22', 'Dra. Ana Belén Arteaga', '3183722096', 'gerencia@pastosaludese.gov.co'),
(900046724, 'ASOCIACION DE CABILDOS UKAWESX NASA CHXAB IPS-I', 'Calle Principal', 'Dra. Aida Liliana Quilcué', '928477403', 'ukawesx@gmail.com'),
(900211406, 'E.S.E. HOSPITAL SAN RAFAEL (SAN VICENTE DEL CAGUAN)', 'Calle 5 No. 0-38', 'Dr. Luis Fernando Rojas', '4644301', 'info@hospitalsrvdelcaguan.gov.co'),
(900656564, 'SUBRED INTEGRADA DE SERVICIOS DE SALUD NORTE E.S.E.', 'Calle 155 No 9 - 45', 'Sr. Felipe Andrés Rincón', '0759359', 'gerenciasubrednorte@subrednorte.gov.co'),
(942000045, 'E.S.E. HOSPITAL DEPARTAMENTAL SAN JUAN DE DIOS (PUERTO CARREÑO)', 'CL 18 No 10-43', 'Sr. Walter Alexander Tovar', '(098) 5654006/ 38544', 'hsanjuandediosvichada@yahoo.com'),
(945000003, 'EMPRESA SOCIAL DEL ESTADO HOSPITAL SAN ANTONIO (MITÚ)', 'CARRERA 13A 15A-127', 'Dra. Benilda María Gómez', '85642285', 'hospitaldesanantonio@hum.gov.co');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `medicamentos`
--

CREATE TABLE `medicamentos` (
  `id_medicamento` int(11) NOT NULL,
  `nom_medicamento` varchar(150) NOT NULL,
  `id_tipo_medic` int(11) NOT NULL,
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `medicamentos`
--

INSERT INTO `medicamentos` (`id_medicamento`, `nom_medicamento`, `id_tipo_medic`, `descripcion`) VALUES
(1, 'Acetaminofen 500mg', 1, 'Analgesico y antipiretico para aliviar dolor leve a moderado y fiebre.'),
(2, 'Ibuprofeno 400mg', 2, 'Antiinflamatorio no esteroideo (AINE) para dolor, inflamacion y fiebre.'),
(3, 'Amoxicilina 500mg', 3, 'Antibiotico penicilinico para tratar infecciones bacterianas.'),
(4, 'Loratadina 10mg', 8, 'Antihistaminico para aliviar sintomas de alergias como rinitis y urticaria.'),
(5, 'Omeprazol 20mg', 16, 'Inhibidor de la bomba de protones para reducir acido estomacal (gastritis, reflujo).'),
(6, 'Losartan 50mg', 6, 'Antagonista del receptor de angiotensina II para tratar la hipertension arterial.'),
(7, 'Metformina 850mg', 7, 'Antidiabetico oral para controlar niveles de azucar en sangre en Diabetes Tipo 2.'),
(8, 'Salbutamol Inhalador 100mcg', 9, 'Broncodilatador de accion corta para alivio rapido de sintomas de asma y EPOC.'),
(9, 'Atorvastatina 20mg', 14, 'Estatina utilizada para reducir los niveles de colesterol y trigliceridos.'),
(10, 'Levotiroxina 100mcg', 24, 'Hormona tiroidea sintetica para tratar el hipotiroidismo.'),
(11, 'Vitamina C 500mg', 22, 'Suplemento vitaminico antioxidante.'),
(12, 'Hidroclorotiazida 25mg', 15, 'Diuretico tiazidico para hipertension y edema.'),
(13, 'Clotrimazol Crema 1%', 5, 'Antifungico topico para infecciones por hongos en la piel.'),
(14, 'Acido Acetilsalicilico 100mg', 2, 'AINE, antiagregante plaquetario (Aspirina).'),
(15, 'Sertralina 50mg', 10, 'Antidepresivo ISRS para depresion y ansiedad.'),
(16, 'Ciprofloxacina 500mg', 3, 'Antibiotico fluoroquinolona para diversas infecciones bacterianas.'),
(17, 'Aciclovir 400mg', 4, 'Antiviral para tratar infecciones por herpes.'),
(18, 'Fluconazol 150mg', 5, 'Antifungico sistemico para infecciones por hongos (candida).'),
(19, 'Amlodipino 5mg', 6, 'Bloqueador de canales de calcio para hipertension y angina.'),
(20, 'Glibenclamida 5mg', 7, 'Sulfonilurea, antidiabetico oral para Diabetes Tipo 2.'),
(21, 'Cetirizina 10mg', 8, 'Antihistaminico de segunda generacion para alergias.'),
(22, 'Budesonida Inhalador 200mcg', 9, 'Corticosteroide inhalado para control de asma.'),
(23, 'Fluoxetina 20mg', 10, 'Antidepresivo ISRS (Prozac).'),
(24, 'Alprazolam 0.5mg', 11, 'Benzodiazepina ansiolitica para ansiedad y panico.'),
(25, 'Risperidona 2mg', 12, 'Antipsicotico atipico para esquizofrenia y trastorno bipolar.'),
(26, 'Warfarina 5mg', 13, 'Anticoagulante oral para prevenir trombosis.'),
(27, 'Gemfibrozilo 600mg', 14, 'Fibrato para reducir trigliceridos altos.'),
(28, 'Furosemida 40mg', 15, 'Diuretico de asa para edema e hipertension.'),
(29, 'Ranitidina 150mg', 16, 'Antagonista H2 para reducir acido estomacal.'),
(30, 'Bisacodilo 5mg', 17, 'Laxante estimulante para estrenimiento ocasional.'),
(31, 'Prednisona 5mg', 18, 'Corticosteroide sistemico antiinflamatorio e inmunosupresor.'),
(32, 'Ciclobenzaprina 10mg', 19, 'Relajante muscular para espasmos musculares.'),
(33, 'Metotrexato 2.5mg', 20, 'Antimetabolito usado en cancer y enfermedades autoinmunes.'),
(34, 'Vacuna Influenza Estacional', 21, 'Inmunizacion para prevenir la gripe estacional.'),
(35, 'Calcio 600mg + Vitamina D 400UI', 22, 'Suplemento para salud osea.'),
(36, 'Etinilestradiol/Levonorgestrel', 23, 'Anticonceptivo oral combinado.'),
(37, 'Acido Valproico 250mg', 25, 'Anticonvulsivante para epilepsia y trastorno bipolar.');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `meridiano`
--

CREATE TABLE `meridiano` (
  `id_periodo` int(11) NOT NULL,
  `periodo` varchar(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `meridiano`
--

INSERT INTO `meridiano` (`id_periodo`, `periodo`) VALUES
(1, 'AM'),
(2, 'PM');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `municipio`
--

CREATE TABLE `municipio` (
  `id_mun` varchar(10) NOT NULL,
  `nom_mun` varchar(100) NOT NULL,
  `id_dep` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `municipio`
--

INSERT INTO `municipio` (`id_mun`, `nom_mun`, `id_dep`) VALUES
('05001', 'Medellin', '05'),
('05002', 'Abejorral', '05'),
('05004', 'Abriaqui', '05'),
('05021', 'Alejandria', '05'),
('05030', 'Amaga', '05'),
('05031', 'Amalfi', '05'),
('05034', 'Andes', '05'),
('05036', 'Angelopolis', '05'),
('05038', 'Angostura', '05'),
('05040', 'Anori', '05'),
('05042', 'Antioquia', '05'),
('05044', 'Anza', '05'),
('05045', 'Apartado', '05'),
('05051', 'Arboletes', '05'),
('05055', 'Argelia', '05'),
('05059', 'Armenia', '05'),
('05079', 'Barbosa', '05'),
('05086', 'Belmira', '05'),
('05088', 'Bello', '05'),
('05091', 'Betania', '05'),
('05093', 'Betulia', '05'),
('05101', 'Bolivar', '05'),
('05107', 'Briceno', '05'),
('05113', 'Buritica', '05'),
('05120', 'Caceres', '05'),
('05125', 'Caicedo', '05'),
('05129', 'Caldas', '05'),
('05134', 'Campamento', '05'),
('05138', 'Canasgordas', '05'),
('05142', 'Caracoli', '05'),
('05145', 'Caramanta', '05'),
('05147', 'Carepa', '05'),
('05148', 'Carmen de viboral', '05'),
('05150', 'Carolina', '05'),
('05154', 'Caucasia', '05'),
('05172', 'Chigorodo', '05'),
('05190', 'Cisneros', '05'),
('05197', 'Cocorna', '05'),
('05206', 'Concepcion', '05'),
('05209', 'Concordia', '05'),
('05212', 'Copacabana', '05'),
('05234', 'Dabeiba', '05'),
('05237', 'Don matias', '05'),
('05240', 'Ebejico', '05'),
('05250', 'El bagre', '05'),
('05264', 'Entrerrios', '05'),
('05266', 'Envigado', '05'),
('05282', 'Fredonia', '05'),
('05284', 'Frontino', '05'),
('05306', 'Giraldo', '05'),
('05308', 'Girardota', '05'),
('05310', 'Gomez plata', '05'),
('05313', 'Granada', '05'),
('05315', 'Guadalupe', '05'),
('05318', 'Guarne', '05'),
('05321', 'Guatape', '05'),
('05347', 'Heliconia', '05'),
('05353', 'Hispania', '05'),
('05360', 'Itagui', '05'),
('05361', 'Ituango', '05'),
('05364', 'Jardin', '05'),
('05368', 'Jerico', '05'),
('05376', 'La ceja', '05'),
('05380', 'La estrella', '05'),
('05390', 'La pintada', '05'),
('05400', 'La union', '05'),
('05411', 'Liborina', '05'),
('05425', 'Maceo', '05'),
('05440', 'Marinilla', '05'),
('05467', 'Montebello', '05'),
('05475', 'Murindo', '05'),
('05480', 'Mutata', '05'),
('05483', 'Narino', '05'),
('05490', 'Necocli', '05'),
('05495', 'Nechi', '05'),
('05501', 'Olaya', '05'),
('05541', 'Penol', '05'),
('05543', 'Peque', '05'),
('05576', 'Pueblorrico', '05'),
('05579', 'Puerto berrio', '05'),
('05585', 'Puerto nare (la magdalena)', '05'),
('05591', 'Puerto triunfo', '05'),
('05604', 'Remedios', '05'),
('05607', 'Retiro', '05'),
('05615', 'Rionegro', '05'),
('05628', 'Sabanalarga', '05'),
('05631', 'Sabaneta', '05'),
('05642', 'Salgar', '05'),
('05647', 'San andres', '05'),
('05649', 'San carlos', '05'),
('05652', 'San francisco', '05'),
('05656', 'San jeronimo', '05'),
('05658', 'San jose de la montana', '05'),
('05659', 'San juan de uraba', '05'),
('05660', 'San luis', '05'),
('05664', 'San pedro', '05'),
('05665', 'San pedro de uraba', '05'),
('05667', 'San rafael', '05'),
('05670', 'San roque', '05'),
('05674', 'San vicente', '05'),
('05679', 'Santa barbara', '05'),
('05686', 'Santa rosa de osos', '05'),
('05690', 'Santo domingo', '05'),
('05697', 'Santuario', '05'),
('05736', 'Segovia', '05'),
('05756', 'Sonson', '05'),
('05761', 'Sopetran', '05'),
('05789', 'Tamesis', '05'),
('05790', 'Taraza', '05'),
('05792', 'Tarso', '05'),
('05809', 'Titiribi', '05'),
('05819', 'Toledo', '05'),
('05837', 'Turbo', '05'),
('05842', 'Uramita', '05'),
('05847', 'Urrao', '05'),
('05854', 'Valdivia', '05'),
('05856', 'Valparaiso', '05'),
('05858', 'Vegachi', '05'),
('05861', 'Venecia', '05'),
('05873', 'Vigia del fuerte', '05'),
('05885', 'Yali', '05'),
('05887', 'Yarumal', '05'),
('05890', 'Yolombo', '05'),
('05893', 'Yondo', '05'),
('05895', 'Zaragoza', '05'),
('08001', 'Barranquilla', '08'),
('08078', 'Baranoa', '08'),
('08137', 'Campo de la cruz', '08'),
('08141', 'Candelaria', '08'),
('08296', 'Galapa', '08'),
('08372', 'Juan de acosta', '08'),
('08421', 'Luruaco', '08'),
('08433', 'Malambo', '08'),
('08436', 'Manati', '08'),
('08520', 'Palmar de varela', '08'),
('08549', 'Piojo', '08'),
('08558', 'Polo nuevo', '08'),
('08560', 'Ponedera', '08'),
('08573', 'Puerto colombia', '08'),
('08606', 'Repelon', '08'),
('08634', 'Sabanagrande', '08'),
('08638', 'Sabanalarga', '08'),
('08675', 'Santa lucia', '08'),
('08685', 'Santo tomas', '08'),
('08758', 'Soledad', '08'),
('08770', 'Suan', '08'),
('08832', 'Tubara', '08'),
('08849', 'Usiacuri', '08'),
('11001', 'Usaquen', '11'),
('11002', 'Chapinero', '11'),
('11003', 'Santa fe', '11'),
('11004', 'San cristobal', '11'),
('11005', 'Usme', '11'),
('11006', 'Tunjuelito', '11'),
('11007', 'Bosa', '11'),
('11008', 'Kennedy', '11'),
('11009', 'Fontibon', '11'),
('11010', 'Engativa', '11'),
('11011', 'Suba', '11'),
('11012', 'Barrios unidos', '11'),
('11013', 'Teusaquillo', '11'),
('11014', 'Martires', '11'),
('11015', 'Antonio narino', '11'),
('11016', 'Puente aranda', '11'),
('11017', 'Candelaria', '11'),
('11018', 'Rafael uribe', '11'),
('11019', 'Ciudad bolivar', '11'),
('11020', 'Sumapaz', '11'),
('13001', 'Cartagena', '13'),
('13006', 'Achi', '13'),
('13030', 'Altos del rosario', '13'),
('13042', 'Arenal', '13'),
('13052', 'Arjona', '13'),
('13062', 'Arroyohondo', '13'),
('13074', 'Barranco de loba', '13'),
('13140', 'Calamar', '13'),
('13160', 'Cantagallo', '13'),
('13188', 'Cicuto', '13'),
('13212', 'Cordoba', '13'),
('13222', 'Clemencia', '13'),
('13244', 'El carmen de bolivar', '13'),
('13248', 'El guamo', '13'),
('13268', 'El penon', '13'),
('13300', 'Hatillo de loba', '13'),
('13430', 'Magangue', '13'),
('13433', 'Mahates', '13'),
('13440', 'Margarita', '13'),
('13442', 'Maria la baja', '13'),
('13458', 'Montecristo', '13'),
('13468', 'Mompos', '13'),
('13473', 'Morales', '13'),
('13549', 'Pinillos', '13'),
('13580', 'Regidor', '13'),
('13600', 'Rio viejo', '13'),
('13620', 'San cristobal', '13'),
('13647', 'San estanislao', '13'),
('13650', 'San fernando', '13'),
('13654', 'San jacinto', '13'),
('13655', 'San jacinto del cauca', '13'),
('13657', 'San juan nepomuceno', '13'),
('13667', 'San martin de loba', '13'),
('13670', 'San pablo', '13'),
('13673', 'Santa catalina', '13'),
('13683', 'Santa rosa', '13'),
('13688', 'Santa rosa del sur', '13'),
('13744', 'Simiti', '13'),
('13760', 'Soplaviento', '13'),
('13780', 'Talaigua nuevo', '13'),
('13810', 'Tiquisio (puerto rico)', '13'),
('13836', 'Turbaco', '13'),
('13838', 'Turbana', '13'),
('13873', 'Villanueva', '13'),
('13894', 'Zambrano', '13'),
('15001', 'Tunja', '15'),
('15022', 'Almeida', '15'),
('15047', 'Aquitania', '15'),
('15051', 'Arcabuco', '15'),
('15087', 'Belen', '15'),
('15090', 'Berbeo', '15'),
('15092', 'Betetitiva', '15'),
('15097', 'Boavita', '15'),
('15104', 'Boyaca', '15'),
('15106', 'Briceno', '15'),
('15109', 'Buenavista', '15'),
('15114', 'Busbanza', '15'),
('15131', 'Caldas', '15'),
('15135', 'Campohermoso', '15'),
('15162', 'Cerinza', '15'),
('15172', 'Chinavita', '15'),
('15176', 'Chiquinquira', '15'),
('15180', 'Chiscas', '15'),
('15183', 'Chita', '15'),
('15185', 'Chitaraque', '15'),
('15187', 'Chivata', '15'),
('15189', 'Cienega', '15'),
('15204', 'Combita', '15'),
('15212', 'Coper', '15'),
('15215', 'Corrales', '15'),
('15218', 'Covarachia', '15'),
('15223', 'Cubara', '15'),
('15224', 'Cucaita', '15'),
('15226', 'Cuitiva', '15'),
('15232', 'Chiquiza', '15'),
('15236', 'Chivor', '15'),
('15238', 'Duitama', '15'),
('15244', 'El cocuy', '15'),
('15248', 'El espino', '15'),
('15272', 'Firavitoba', '15'),
('15276', 'Floresta', '15'),
('15293', 'Gachantiva', '15'),
('15296', 'Gameza', '15'),
('15299', 'Garagoa', '15'),
('15317', 'Guacamayas', '15'),
('15322', 'Guateque', '15'),
('15325', 'Guayata', '15'),
('15332', 'Guican', '15'),
('15362', 'Iza', '15'),
('15367', 'Jenesano', '15'),
('15368', 'Jerico', '15'),
('15377', 'Labranzagrande', '15'),
('15380', 'La capilla', '15'),
('15401', 'La victoria', '15'),
('15403', 'La uvita', '15'),
('15407', 'Villa de leiva', '15'),
('15425', 'Macanal', '15'),
('15442', 'Maripi', '15'),
('15455', 'Miraflores', '15'),
('15464', 'Mongua', '15'),
('15466', 'Mongui', '15'),
('15469', 'Moniquira', '15'),
('15476', 'Motavita', '15'),
('15480', 'Muzo', '15'),
('15491', 'Nobsa', '15'),
('15494', 'Nuevo colon', '15'),
('15500', 'Oicata', '15'),
('15507', 'Otanche', '15'),
('15511', 'Pachavita', '15'),
('15514', 'Paez', '15'),
('15516', 'Paipa', '15'),
('15518', 'Pajarito', '15'),
('15522', 'Panqueba', '15'),
('15531', 'Pauna', '15'),
('15533', 'Paya', '15'),
('15537', 'Paz del rio', '15'),
('15542', 'Pesca', '15'),
('15550', 'Pisba', '15'),
('15572', 'Puerto boyaca', '15'),
('15580', 'Quipama', '15'),
('15599', 'Ramiriqui', '15'),
('15600', 'Raquira', '15'),
('15621', 'Rondon', '15'),
('15632', 'Saboya', '15'),
('15638', 'Sachica', '15'),
('15646', 'Samaca', '15'),
('15660', 'San eduardo', '15'),
('15664', 'San jose de pare', '15'),
('15667', 'San luis de gaceno', '15'),
('15673', 'San mateo', '15'),
('15676', 'San miguel de sema', '15'),
('15681', 'San pablo de borbur', '15'),
('15686', 'Santana', '15'),
('15690', 'Santa maria', '15'),
('15693', 'Santa rosa de viterbo', '15'),
('15696', 'Santa sofia', '15'),
('15720', 'Sativanorte', '15'),
('15723', 'Sativasur', '15'),
('15740', 'Siachoque', '15'),
('15753', 'Soata', '15'),
('15755', 'Socota', '15'),
('15757', 'Socha', '15'),
('15759', 'Sogamoso', '15'),
('15761', 'Somondoco', '15'),
('15762', 'Sora', '15'),
('15763', 'Sotaquira', '15'),
('15764', 'Soraca', '15'),
('15774', 'Susacon', '15'),
('15776', 'Sutamarchan', '15'),
('15778', 'Sutatenza', '15'),
('15790', 'Tasco', '15'),
('15798', 'Tenza', '15'),
('15804', 'Tibana', '15'),
('15806', 'Tibasosa', '15'),
('15808', 'Tinjaca', '15'),
('15810', 'Tipacoque', '15'),
('15814', 'Toca', '15'),
('15816', 'Togui', '15'),
('15820', 'Topaga', '15'),
('15822', 'Tota', '15'),
('15835', 'Tunungua', '15'),
('15837', 'Turmeque', '15'),
('15839', 'Tuta', '15'),
('15842', 'Tutasa', '15'),
('15861', 'Umbita', '15'),
('15879', 'Ventaquemada', '15'),
('15887', 'Zetaquira', '15'),
('15897', 'Viracacha', '15'),
('17001', 'Manizales', '17'),
('17013', 'Aguadas', '17'),
('17042', 'Anserma', '17'),
('17050', 'Aranzazu', '17'),
('17088', 'Belalcazar', '17'),
('17174', 'Chinchina', '17'),
('17272', 'Filadelfia', '17'),
('17380', 'La dorada', '17'),
('17388', 'La merced', '17'),
('17433', 'Manzanares', '17'),
('17442', 'Marmato', '17'),
('17444', 'Marquetalia', '17'),
('17446', 'Marulanda', '17'),
('17486', 'Neira', '17'),
('17495', 'Norcasia', '17'),
('17513', 'Pacora', '17'),
('17524', 'Palestina', '17'),
('17541', 'Pensilvania', '17'),
('17614', 'Riosucio', '17'),
('17616', 'Risaralda', '17'),
('17653', 'Salamina', '17'),
('17662', 'Samana', '17'),
('17665', 'San jose', '17'),
('17777', 'Supia', '17'),
('17867', 'Victoria', '17'),
('17873', 'Villamaria', '17'),
('17877', 'Viterbo', '17'),
('18001', 'Florencia', '18'),
('18029', 'Albania', '18'),
('18094', 'Belen de los andaquies', '18'),
('18150', 'Cartagena del chaira', '18'),
('18205', 'Curillo', '18'),
('18247', 'El doncello', '18'),
('18256', 'El paujil', '18'),
('18410', 'La montanita', '18'),
('18460', 'Milan', '18'),
('18479', 'Morelia', '18'),
('18592', 'Puerto rico', '18'),
('18610', 'San jose del fragua', '18'),
('18753', 'San vicente del caguan', '18'),
('18756', 'Solano', '18'),
('18785', 'Solita', '18'),
('18860', 'Valparaiso', '18'),
('19001', 'Popayan', '19'),
('19022', 'Almaguer', '19'),
('19050', 'Argelia', '19'),
('19075', 'Balboa', '19'),
('19100', 'Bolivar', '19'),
('19110', 'Buenos aires', '19'),
('19130', 'Cajibio', '19'),
('19137', 'Caldono', '19'),
('19142', 'Caloto', '19'),
('19212', 'Corinto', '19'),
('19256', 'El tambo', '19'),
('19290', 'Florencia', '19'),
('19318', 'Guapi', '19'),
('19355', 'Inza', '19'),
('19364', 'Jambalo', '19'),
('19392', 'La sierra', '19'),
('19397', 'La vega', '19'),
('19418', 'Lopez (micay)', '19'),
('19450', 'Mercaderes', '19'),
('19455', 'Miranda', '19'),
('19473', 'Morales', '19'),
('19513', 'Padilla', '19'),
('19517', 'Paez (belalcazar)', '19'),
('19532', 'Patia (el bordo)', '19'),
('19533', 'Piamonte', '19'),
('19548', 'Piendamo', '19'),
('19573', 'Puerto tejada', '19'),
('19585', 'Purace (coconuco)', '19'),
('19622', 'Rosas', '19'),
('19693', 'San sebastian', '19'),
('19698', 'Santander de quilichao', '19'),
('19701', 'Santa rosa', '19'),
('19743', 'Silvia', '19'),
('19760', 'Sotara (paispamba)', '19'),
('19780', 'Suarez', '19'),
('19785', 'Sucre', '19'),
('19807', 'Timbio', '19'),
('19809', 'Timbiqui', '19'),
('19821', 'Toribio', '19'),
('19824', 'Totoro', '19'),
('19845', 'Villarica', '19'),
('20001', 'Valledupar', '20'),
('20011', 'Aguachica', '20'),
('20013', 'Agustin codazzi', '20'),
('20032', 'Astrea', '20'),
('20045', 'Becerril', '20'),
('20060', 'Bosconia', '20'),
('20175', 'Chimichagua', '20'),
('20178', 'Chiriguana', '20'),
('20228', 'Curumani', '20'),
('20238', 'El copey', '20'),
('20250', 'El paso', '20'),
('20295', 'Gamarra', '20'),
('20310', 'Gonzalez', '20'),
('20383', 'La gloria', '20'),
('20400', 'La jagua ibirico', '20'),
('20443', 'Manaure (balcon del cesar)', '20'),
('20517', 'Pailitas', '20'),
('20550', 'Pelaya', '20'),
('20570', 'Pueblo bello', '20'),
('20614', 'Rio de oro', '20'),
('20621', 'La paz (robles)', '20'),
('20710', 'San alberto', '20'),
('20750', 'San diego', '20'),
('20770', 'San martin', '20'),
('20787', 'Tamalameque', '20'),
('23001', 'Monteria', '23'),
('23068', 'Ayapel', '23'),
('23079', 'Buenavista', '23'),
('23090', 'Canalete', '23'),
('23162', 'Cerete', '23'),
('23168', 'Chima', '23'),
('23182', 'Chinu', '23'),
('23189', 'Cienaga de oro', '23'),
('23300', 'Cotorra', '23'),
('23350', 'La apartada', '23'),
('23417', 'Lorica', '23'),
('23419', 'Los cordobas', '23'),
('23464', 'Momil', '23'),
('23466', 'Montelibano', '23'),
('23500', 'Monitos', '23'),
('23555', 'Planeta rica', '23'),
('23570', 'Pueblo nuevo', '23'),
('23574', 'Puerto escondido', '23'),
('23580', 'Puerto libertador', '23'),
('23586', 'Purisima', '23'),
('23660', 'Sahagun', '23'),
('23670', 'San andres sotavento', '23'),
('23672', 'San antero', '23'),
('23675', 'San bernardo del viento', '23'),
('23678', 'San carlos', '23'),
('23686', 'San pelayo', '23'),
('23807', 'Tierralta', '23'),
('23855', 'Valencia', '23'),
('25001', 'Agua de dios', '25'),
('25019', 'Alban', '25'),
('25035', 'Anapoima', '25'),
('25040', 'Anolaima', '25'),
('25053', 'Arbelaez', '25'),
('25086', 'Beltran', '25'),
('25095', 'Bituima', '25'),
('25099', 'Bojaca', '25'),
('25120', 'Cabrera', '25'),
('25123', 'Cachipay', '25'),
('25126', 'Cajica', '25'),
('25148', 'Caparrapi', '25'),
('25151', 'Caqueza', '25'),
('25154', 'Carmen de carupa', '25'),
('25168', 'Chaguani', '25'),
('25175', 'Chia', '25'),
('25178', 'Chipaque', '25'),
('25181', 'Choachi', '25'),
('25183', 'Choconta', '25'),
('25200', 'Cogua', '25'),
('25214', 'Cota', '25'),
('25224', 'Cucunuba', '25'),
('25245', 'El colegio', '25'),
('25258', 'El penon', '25'),
('25260', 'El rosal', '25'),
('25269', 'Facatativa', '25'),
('25279', 'Fomeque', '25'),
('25281', 'Fosca', '25'),
('25286', 'Funza', '25'),
('25288', 'Fuquene', '25'),
('25290', 'Fusagasuga', '25'),
('25293', 'Gachala', '25'),
('25295', 'Gachancipa', '25'),
('25297', 'Gacheta', '25'),
('25299', 'Gama', '25'),
('25307', 'Girardot', '25'),
('25312', 'Granada', '25'),
('25317', 'Guacheta', '25'),
('25320', 'Guaduas', '25'),
('25322', 'Guasca', '25'),
('25324', 'Guataqui', '25'),
('25326', 'Guatavita', '25'),
('25328', 'Guayabal de siquima', '25'),
('25335', 'Guayabetal', '25'),
('25339', 'Gutierrez', '25'),
('25368', 'Jerusalen', '25'),
('25372', 'Junin', '25'),
('25377', 'La calera', '25'),
('25386', 'La mesa', '25'),
('25394', 'La palma', '25'),
('25398', 'La pena', '25'),
('25402', 'La vega', '25'),
('25407', 'Lenguazaque', '25'),
('25426', 'Macheta', '25'),
('25430', 'Madrid', '25'),
('25436', 'Manta', '25'),
('25438', 'Medina', '25'),
('25473', 'Mosquera', '25'),
('25483', 'Narino', '25'),
('25486', 'Nemocon', '25'),
('25488', 'Nilo', '25'),
('25489', 'Nimaima', '25'),
('25491', 'Nocaima', '25'),
('25506', 'Venecia (ospina perez)', '25'),
('25513', 'Pacho', '25'),
('25518', 'Paime', '25'),
('25524', 'Pandi', '25'),
('25530', 'Paratebueno', '25'),
('25535', 'Pasca', '25'),
('25572', 'Puerto salgar', '25'),
('25580', 'Puli', '25'),
('25592', 'Quebradanegra', '25'),
('25594', 'Quetame', '25'),
('25596', 'Quipile', '25'),
('25599', 'Apulo (rafael reyes)', '25'),
('25612', 'Ricaurte', '25'),
('25645', 'San antonio del tequendama', '25'),
('25649', 'San bernardo', '25'),
('25653', 'San cayetano', '25'),
('25658', 'San francisco', '25'),
('25662', 'San juan de rioseco', '25'),
('25718', 'Sasaima', '25'),
('25736', 'Sesquile', '25'),
('25740', 'Sibate', '25'),
('25743', 'Silvania', '25'),
('25745', 'Simijaca', '25'),
('25754', 'Soacha', '25'),
('25758', 'Sopo', '25'),
('25769', 'Subachoque', '25'),
('25772', 'Suesca', '25'),
('25777', 'Supata', '25'),
('25779', 'Susa', '25'),
('25781', 'Sutatausa', '25'),
('25785', 'Tabio', '25'),
('25793', 'Tausa', '25'),
('25797', 'Tena', '25'),
('25799', 'Tenjo', '25'),
('25805', 'Tibacuy', '25'),
('25807', 'Tibirita', '25'),
('25815', 'Tocaima', '25'),
('25817', 'Tocancipa', '25'),
('25823', 'Topaipi', '25'),
('25839', 'Ubala', '25'),
('25841', 'Ubaque', '25'),
('25843', 'Ubate', '25'),
('25845', 'Une', '25'),
('25851', 'Utica', '25'),
('25862', 'Vergara', '25'),
('25867', 'Viani', '25'),
('25871', 'Villagomez', '25'),
('25873', 'Villapinzon', '25'),
('25875', 'Villeta', '25'),
('25878', 'Viota', '25'),
('25885', 'Yacopi', '25'),
('25898', 'Zipacon', '25'),
('25899', 'Zipaquira', '25'),
('27001', 'Quibdo', '27'),
('27006', 'Acandi', '27'),
('27025', 'Alto baudo (pie de pato)', '27'),
('27050', 'Atrato', '27'),
('27073', 'Bagado', '27'),
('27075', 'Bahia solano (mutis)', '27'),
('27077', 'Bajo baudo (pizarro)', '27'),
('27099', 'Bojaya (bellavista)', '27'),
('27135', 'Canton de san pablo', '27'),
('27205', 'Condoto', '27'),
('27245', 'El carmen de atrato', '27'),
('27250', 'Litoral del bajo san juan', '27'),
('27361', 'Istmina', '27'),
('27372', 'Jurado', '27'),
('27413', 'Lloro', '27'),
('27425', 'Medio atrato', '27'),
('27430', 'Medio baudo', '27'),
('27450', 'Medio san juan', '27'),
('27491', 'Novita', '27'),
('27495', 'Nuqui', '27'),
('27580', 'Rio quito', '27'),
('27600', 'Riosucio', '27'),
('27615', 'San jose del palmar', '27'),
('27745', 'Sipi', '27'),
('27787', 'Tado', '27'),
('27800', 'Unguia', '27'),
('27810', 'Union panamericana', '27'),
('41001', 'Neiva', '41'),
('41006', 'Acevedo', '41'),
('41013', 'Agrado', '41'),
('41016', 'Aipe', '41'),
('41020', 'Algeciras', '41'),
('41026', 'Altamira', '41'),
('41078', 'Baraya', '41'),
('41132', 'Campoalegre', '41'),
('41206', 'Colombia', '41'),
('41244', 'Elias', '41'),
('41298', 'Garzon', '41'),
('41306', 'Gigante', '41'),
('41319', 'Guadalupe', '41'),
('41349', 'Hobo', '41'),
('41357', 'Iquira', '41'),
('41359', 'Isnos (san jose de isnos)', '41'),
('41378', 'La argentina', '41'),
('41396', 'La plata', '41'),
('41483', 'Nataga', '41'),
('41503', 'Oporapa', '41'),
('41518', 'Paicol', '41'),
('41524', 'Palermo', '41'),
('41530', 'Palestina', '41'),
('41548', 'Pital', '41'),
('41551', 'Pitalito', '41'),
('41615', 'Rivera', '41'),
('41660', 'Saladoblanco', '41'),
('41668', 'San agustin', '41'),
('41676', 'Santa maria', '41'),
('41770', 'Suaza', '41'),
('41791', 'Tarqui', '41'),
('41797', 'Tello', '41'),
('41799', 'Tesalia', '41'),
('41801', 'Teruel', '41'),
('41807', 'Timana', '41'),
('41872', 'Villavieja', '41'),
('41885', 'Yaguara', '41'),
('44001', 'Riohacha', '44'),
('44078', 'Barrancas', '44'),
('44090', 'Dibulla', '44'),
('44098', 'Distraccion', '44'),
('44110', 'El molino', '44'),
('44279', 'Fonseca', '44'),
('44378', 'Hatonuevo', '44'),
('44394', 'La jagua del pilar', '44'),
('44430', 'Maicao', '44'),
('44560', 'Manaure', '44'),
('44650', 'San juan del cesar', '44'),
('44847', 'Uribia', '44'),
('44855', 'Urumita', '44'),
('44874', 'Villanueva', '44'),
('47001', 'Santa marta', '47'),
('47030', 'Algarrobo', '47'),
('47053', 'Aracataca', '47'),
('47058', 'Ariguani (el dificil)', '47'),
('47161', 'Cerro san antonio', '47'),
('47170', 'Chivolo', '47'),
('47205', 'Cienaga', '47'),
('47245', 'Concordia', '47'),
('47258', 'El banco', '47'),
('47268', 'El pinon', '47'),
('47288', 'El reten', '47'),
('47318', 'Fundacion', '47'),
('47460', 'Guamal', '47'),
('47541', 'Pedraza', '47'),
('47545', 'Pijino del carmen (pijino)', '47'),
('47551', 'Pivijay', '47'),
('47555', 'Plato', '47'),
('47570', 'Puebloviejo', '47'),
('47605', 'Remolino', '47'),
('47660', 'Sabanas de san angel', '47'),
('47675', 'Salamina', '47'),
('47692', 'San sebastian de buenavista', '47'),
('47703', 'San zenon', '47'),
('47707', 'Santa ana', '47'),
('47720', 'Santa barbara de pinto', '47'),
('47745', 'Sitionuevo', '47'),
('47798', 'Tenerife', '47'),
('47960', 'Zapayan', '47'),
('47980', 'Zona bananera', '47'),
('50001', 'Villavicencio', '50'),
('50006', 'Acacias', '50'),
('50110', 'Barranca de upia', '50'),
('50124', 'Cabuyaro', '50'),
('50150', 'Castilla la nueva', '50'),
('50223', 'San luis de cubarral', '50'),
('50226', 'Cumaral', '50'),
('50245', 'El calvario', '50'),
('50251', 'El castillo', '50'),
('50270', 'El dorado', '50'),
('50287', 'Fuente de oro', '50'),
('50313', 'Granada', '50'),
('50318', 'Guamal', '50'),
('50325', 'Mapiripan', '50'),
('50330', 'Mesetas', '50'),
('50350', 'La macarena', '50'),
('50370', 'La uribe', '50'),
('50400', 'Lejanias', '50'),
('50450', 'Puerto gaitan', '50'),
('50568', 'Puerto concordia', '50'),
('50573', 'Puerto lopez', '50'),
('50577', 'Puerto lleras', '50'),
('50590', 'Puerto rico', '50'),
('50606', 'Restrepo', '50'),
('50680', 'San carlos de guaroa', '50'),
('50683', 'San juan de arama', '50'),
('50686', 'San juanito', '50'),
('50689', 'San martin', '50'),
('50711', 'Vistahermosa', '50'),
('52001', 'Pasto', '52'),
('52019', 'Alban (san jose)', '52'),
('52022', 'Aldana', '52'),
('52036', 'Ancuya', '52'),
('52051', 'Arboleda (berruecos)', '52'),
('52079', 'Barbacoas', '52'),
('52083', 'Belen', '52'),
('52110', 'Buesaco', '52'),
('52120', 'Colon (genova)', '52'),
('52203', 'Consaca', '52'),
('52207', 'Contadero', '52'),
('52210', 'Cordoba', '52'),
('52215', 'Cuaspud (carlosama)', '52'),
('52224', 'Cumbal', '52'),
('52227', 'Cumbitara', '52'),
('52233', 'Chachagui', '52'),
('52240', 'El charco', '52'),
('52250', 'El penol', '52'),
('52254', 'El rosario', '52'),
('52256', 'El tablon', '52'),
('52258', 'El tambo', '52'),
('52260', 'Funes', '52'),
('52287', 'Guachucal', '52'),
('52317', 'Guaitarilla', '52'),
('52320', 'Gualmatan', '52'),
('52323', 'Iles', '52'),
('52352', 'Imues', '52'),
('52354', 'Ipiales', '52'),
('52356', 'La cruz', '52'),
('52378', 'La florida', '52'),
('52381', 'La llanada', '52'),
('52385', 'La tola', '52'),
('52390', 'La union', '52'),
('52399', 'Leiva', '52'),
('52405', 'Linares', '52'),
('52411', 'Los andes (sotomayor)', '52'),
('52418', 'Magui (payan)', '52'),
('52427', 'Mallama (piedrancha)', '52'),
('52435', 'Mosquera', '52'),
('52473', 'Narino', '52'),
('52480', 'Olaya herrera (bocas de satinga)', '52'),
('52490', 'Ospina', '52'),
('52506', 'Francisco pizarro (salahonda)', '52'),
('52520', 'Policarpa', '52'),
('52540', 'Potosi', '52'),
('52560', 'Providencia', '52'),
('52565', 'Puerres', '52'),
('52573', 'Pupiales', '52'),
('52585', 'Ricaurte', '52'),
('52612', 'Roberto payan (san jose)', '52'),
('52621', 'Samaniego', '52'),
('52678', 'Sandona', '52'),
('52683', 'San bernardo', '52'),
('52685', 'San lorenzo', '52'),
('52687', 'San pablo', '52'),
('52693', 'San pedro de cartago', '52'),
('52696', 'Santa barbara (iscuande)', '52'),
('52699', 'Santa cruz (guachaves)', '52'),
('52720', 'Sapuyes', '52'),
('52786', 'Taminango', '52'),
('52788', 'Tangua', '52'),
('52835', 'Tumaco', '52'),
('52838', 'Tuquerres', '52'),
('52885', 'Yacuanquer', '52'),
('54001', 'Cucuta', '54'),
('54003', 'Abrego', '54'),
('54051', 'Arboledas', '54'),
('54099', 'Bochalema', '54'),
('54109', 'Bucarasica', '54'),
('54125', 'Cacota', '54'),
('54128', 'Cachira', '54'),
('54172', 'Chinacota', '54'),
('54174', 'Chitaga', '54'),
('54206', 'Convencion', '54'),
('54223', 'Cucutilla', '54'),
('54239', 'Durania', '54'),
('54245', 'El carmen', '54'),
('54250', 'El tarra', '54'),
('54261', 'El zulia', '54'),
('54313', 'Gramalote', '54'),
('54344', 'Hacari', '54'),
('54347', 'Herran', '54'),
('54377', 'Labateca', '54'),
('54385', 'La esperanza', '54'),
('54398', 'La playa', '54'),
('54405', 'Los patios', '54'),
('54418', 'Lourdes', '54'),
('54480', 'Mutiscua', '54'),
('54498', 'Ocana', '54'),
('54518', 'Pamplona', '54'),
('54520', 'Pamplonita', '54'),
('54553', 'Puerto santander', '54'),
('54599', 'Ragonvalia', '54'),
('54660', 'Salazar', '54'),
('54670', 'San calixto', '54'),
('54673', 'San cayetano', '54'),
('54680', 'Santiago', '54'),
('54720', 'Sardinata', '54'),
('54743', 'Silos', '54'),
('54800', 'Teorama', '54'),
('54810', 'Tibu', '54'),
('54820', 'Toledo', '54'),
('54871', 'Villa caro', '54'),
('54874', 'Villa del rosario', '54'),
('63001', 'Armenia', '63'),
('63111', 'Buenavista', '63'),
('63130', 'Calarca', '63'),
('63190', 'Circasia', '63'),
('63212', 'Cordoba', '63'),
('63272', 'Filandia', '63'),
('63302', 'Genova', '63'),
('63401', 'La tebaida', '63'),
('63470', 'Montenegro', '63'),
('63548', 'Pijao', '63'),
('63594', 'Quimbaya', '63'),
('63690', 'Salento', '63'),
('66001', 'Pereira', '66'),
('66045', 'Apia', '66'),
('66075', 'Balboa', '66'),
('66088', 'Belen de umbria', '66'),
('66170', 'Dosquebradas', '66'),
('66318', 'Guatica', '66'),
('66383', 'La celia', '66'),
('66400', 'La virginia', '66'),
('66440', 'Marsella', '66'),
('66456', 'Mistrato', '66'),
('66572', 'Pueblo rico', '66'),
('66594', 'Quinchia', '66'),
('66682', 'Santa rosa de cabal', '66'),
('66687', 'Santuario', '66'),
('68001', 'Bucaramanga', '68'),
('68013', 'Aguada', '68'),
('68020', 'Albania', '68'),
('68051', 'Aratoca', '68'),
('68077', 'Barbosa', '68'),
('68079', 'Barichara', '68'),
('68081', 'Barrancabermeja', '68'),
('68092', 'Betulia', '68'),
('68101', 'Bolivar', '68'),
('68121', 'Cabrera', '68'),
('68132', 'California', '68'),
('68147', 'Capitanejo', '68'),
('68152', 'Carcasi', '68'),
('68160', 'Cepita', '68'),
('68162', 'Cerrito', '68'),
('68167', 'Charala', '68'),
('68169', 'Charta', '68'),
('68176', 'Chima', '68'),
('68179', 'Chipata', '68'),
('68190', 'Cimitarra', '68'),
('68207', 'Concepcion', '68'),
('68209', 'Confines', '68'),
('68211', 'Contratacion', '68'),
('68217', 'Coromoro', '68'),
('68229', 'Curiti', '68'),
('68235', 'El carmen de chucury', '68'),
('68245', 'El guacamayo', '68'),
('68250', 'El penon', '68'),
('68255', 'El playon', '68'),
('68264', 'Encino', '68'),
('68266', 'Enciso', '68'),
('68271', 'Florian', '68'),
('68276', 'Floridablanca', '68'),
('68298', 'Gambita', '68'),
('68307', 'Giron', '68'),
('68318', 'Guaca', '68'),
('68320', 'Guadalupe', '68'),
('68322', 'Guapota', '68'),
('68324', 'Guavata', '68'),
('68327', 'Guepsa', '68'),
('68344', 'Hato', '68'),
('68368', 'Jesus maria', '68'),
('68370', 'Jordan', '68'),
('68377', 'La belleza', '68'),
('68385', 'Landazuri', '68'),
('68397', 'La paz', '68'),
('68406', 'Lebrija', '68'),
('68418', 'Los santos', '68'),
('68425', 'Macaravita', '68'),
('68432', 'Malaga', '68'),
('68444', 'Matanza', '68'),
('68464', 'Mogotes', '68'),
('68468', 'Molagavita', '68'),
('68476', 'Ocamonte', '68'),
('68498', 'Oiba', '68'),
('68500', 'Onzaga', '68'),
('68520', 'Palmar', '68'),
('68522', 'Palmas del socorro', '68'),
('68524', 'Paramo', '68'),
('68533', 'Piedecuesta', '68'),
('68547', 'Pinchote', '68'),
('68549', 'Puente nacional', '68'),
('68572', 'Puerto parra', '68'),
('68573', 'Puerto wilches', '68'),
('68575', 'Rionegro', '68'),
('68615', 'Sabana de torres', '68'),
('68655', 'San andres', '68'),
('68669', 'San benito', '68'),
('68673', 'San gil', '68'),
('68679', 'San joaquin', '68'),
('68682', 'San jose de miranda', '68'),
('68686', 'San miguel', '68'),
('68689', 'San vicente de chucuri', '68'),
('68705', 'Santa barbara', '68'),
('68720', 'Santa helena del opon', '68'),
('68745', 'Simacota', '68'),
('68755', 'Socorro', '68'),
('68770', 'Suaita', '68'),
('68773', 'Sucre', '68'),
('68780', 'Surata', '68'),
('68820', 'Tona', '68'),
('68855', 'Valle san jose', '68'),
('68861', 'Velez', '68'),
('68867', 'Vetas', '68'),
('68872', 'Villanueva', '68'),
('68895', 'Zapatoca', '68'),
('70001', 'Sincelejo', '70'),
('70110', 'Buenavista', '70'),
('70124', 'Caimito', '70'),
('70204', 'Coloso (ricaurte)', '70'),
('70215', 'Corozal', '70'),
('70221', 'Covenas', '70'),
('70230', 'Chalan', '70'),
('70235', 'Galeras (nueva granada)', '70'),
('70265', 'Guaranda', '70'),
('70400', 'La union', '70'),
('70418', 'Los palmitos', '70'),
('70429', 'Majagual', '70'),
('70473', 'Morroa', '70'),
('70508', 'Ovejas', '70'),
('70523', 'Palmito', '70'),
('70670', 'Sampues', '70'),
('70678', 'San benito abad', '70'),
('70702', 'San juan de betulia', '70'),
('70708', 'San marcos', '70'),
('70713', 'San onofre', '70'),
('70717', 'San pedro', '70'),
('70742', 'Since', '70'),
('70771', 'Sucre', '70'),
('70820', 'Tolu', '70'),
('70823', 'Toluviejo', '70'),
('73001', 'Ibague', '73'),
('73024', 'Alpujarra', '73'),
('73026', 'Alvarado', '73'),
('73030', 'Ambalema', '73'),
('73043', 'Anzoategui', '73'),
('73055', 'Armero (guayabal)', '73'),
('73067', 'Ataco', '73'),
('73124', 'Cajamarca', '73'),
('73148', 'Carmen de apicala', '73'),
('73152', 'Casabianca', '73'),
('73168', 'Chaparral', '73'),
('73200', 'Coello', '73'),
('73217', 'Coyaima', '73'),
('73226', 'Cunday', '73'),
('73236', 'Dolores', '73'),
('73268', 'Espinal', '73'),
('73270', 'Falan', '73'),
('73275', 'Flandes', '73'),
('73283', 'Fresno', '73'),
('73319', 'Guamo', '73'),
('73347', 'Herveo', '73'),
('73349', 'Honda', '73'),
('73352', 'Icononzo', '73'),
('73408', 'Lerida', '73'),
('73411', 'Libano', '73'),
('73443', 'Mariquita', '73'),
('73449', 'Melgar', '73'),
('73461', 'Murillo', '73'),
('73483', 'Natagaima', '73'),
('73504', 'Ortega', '73'),
('73520', 'Palocabildo', '73'),
('73547', 'Piedras', '73'),
('73555', 'Planadas', '73'),
('73563', 'Prado', '73'),
('73585', 'Purificacion', '73'),
('73616', 'Rioblanco', '73'),
('73622', 'Roncesvalles', '73'),
('73624', 'Rovira', '73'),
('73671', 'Saldana', '73'),
('73675', 'San antonio', '73'),
('73678', 'San luis', '73'),
('73686', 'Santa isabel', '73'),
('73770', 'Suarez', '73'),
('73854', 'Valle de san juan', '73'),
('73861', 'Venadillo', '73'),
('73870', 'Villahermosa', '73'),
('73873', 'Villarrica', '73'),
('76001', 'Cali', '76'),
('76020', 'Alcala', '76'),
('76036', 'Andalucia', '76'),
('76041', 'Ansermanuevo', '76'),
('76054', 'Argelia', '76'),
('76100', 'Bolivar', '76'),
('76109', 'Buenaventura', '76'),
('76111', 'Buga', '76'),
('76113', 'Bugalagrande', '76'),
('76122', 'Caicedonia', '76'),
('76126', 'Calima (darien)', '76'),
('76130', 'Candelaria', '76'),
('76147', 'Cartago', '76'),
('76233', 'Dagua', '76'),
('76243', 'El aguila', '76'),
('76246', 'El cairo', '76'),
('76248', 'El cerrito', '76'),
('76250', 'El dovio', '76'),
('76275', 'Florida', '76'),
('76306', 'Ginebra', '76'),
('76318', 'Guacari', '76'),
('76364', 'Jamundi', '76'),
('76377', 'La cumbre', '76'),
('76400', 'La union', '76'),
('76403', 'La victoria', '76'),
('76497', 'Obando', '76'),
('76520', 'Palmira', '76'),
('76563', 'Pradera', '76'),
('76606', 'Restrepo', '76'),
('76616', 'Riofrio', '76'),
('76622', 'Roldanillo', '76'),
('76670', 'San pedro', '76'),
('76736', 'Sevilla', '76'),
('76823', 'Toro', '76'),
('76828', 'Trujillo', '76'),
('76834', 'Tulua', '76'),
('76845', 'Ulloa', '76'),
('76863', 'Versalles', '76'),
('76869', 'Vijes', '76'),
('76890', 'Yotoco', '76'),
('76892', 'Yumbo', '76'),
('76895', 'Zarzal', '76'),
('81001', 'Arauca', '81'),
('81065', 'Arauquita', '81'),
('81220', 'Cravo norte', '81'),
('81300', 'Fortul', '81'),
('81591', 'Puerto rondon', '81'),
('81736', 'Saravena', '81'),
('81794', 'Tame', '81'),
('85001', 'Yopal', '85'),
('85010', 'Aguazul', '85'),
('85015', 'Chameza', '85'),
('85125', 'Hato corozal', '85'),
('85136', 'La salina', '85'),
('85139', 'Mani', '85'),
('85162', 'Monterrey', '85'),
('85225', 'Nunchia', '85'),
('85230', 'Orocue', '85'),
('85250', 'Paz de ariporo', '85'),
('85263', 'Pore', '85'),
('85279', 'Recetor', '85'),
('85300', 'Sabanalarga', '85'),
('85315', 'Sacama', '85'),
('85325', 'San luis de palenque', '85'),
('85400', 'Tamara', '85'),
('85410', 'Tauramena', '85'),
('85430', 'Trinidad', '85'),
('85440', 'Villanueva', '85'),
('86001', 'Mocoa', '86'),
('86219', 'Colon', '86'),
('86320', 'Orito', '86'),
('86568', 'Puerto asis', '86'),
('86569', 'Puerto caicedo', '86'),
('86571', 'Puerto guzman', '86'),
('86573', 'Puerto leguizamo', '86'),
('86749', 'Sibundoy', '86'),
('86755', 'San francisco', '86'),
('86757', 'San miguel (la dorada)', '86'),
('86760', 'Santiago', '86'),
('86865', 'La hormiga (valle del guamuez)', '86'),
('86885', 'Villagarzon', '86'),
('88001', 'San andres', '88'),
('88564', 'Providencia', '88'),
('91001', 'Leticia', '91'),
('91263', 'El encanto', '91'),
('91405', 'La chorrera', '91'),
('91407', 'La pedrera', '91'),
('91430', 'La victoria', '91'),
('91460', 'Miriti-parana', '91'),
('91530', 'Puerto alegria', '91'),
('91536', 'Puerto arica', '91'),
('91540', 'Puerto narino', '91'),
('91669', 'Puerto santander', '91'),
('91798', 'Tarapaca', '91'),
('94001', 'Inirida', '94'),
('94343', 'Barranco minas', '94'),
('94883', 'San felipe', '94'),
('94884', 'Puerto colombia', '94'),
('94885', 'La guadalupe', '94'),
('94886', 'Cacahual', '94'),
('94887', 'Pana pana (campo alegre)', '94'),
('94888', 'Morichal (morichal nuevo)', '94'),
('95001', 'San jose del guaviare', '95'),
('95015', 'Calamar', '95'),
('95025', 'El retorno', '95'),
('95200', 'Miraflores', '95'),
('97001', 'Mitu', '97'),
('97161', 'Caruru', '97'),
('97511', 'Pacoa', '97'),
('97666', 'Taraira', '97'),
('97777', 'Papunaua (morichal)', '97'),
('97889', 'Yavarate', '97'),
('99001', 'Puerto carreno', '99'),
('99524', 'La primavera', '99'),
('99572', 'Santa rita', '99'),
('99624', 'Santa rosalia', '99'),
('99666', 'San jose de ocune', '99'),
('99773', 'Cumaribo', '99');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `procedimientos`
--

CREATE TABLE `procedimientos` (
  `id_proced` int(11) NOT NULL,
  `procedimiento` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `procedimientos`
--

INSERT INTO `procedimientos` (`id_proced`, `procedimiento`) VALUES
(1, 'Consulta General'),
(2, 'Consulta Especial.'),
(3, 'Toma Presion Art.'),
(4, 'Glucometria'),
(5, 'Hemograma Completo'),
(6, 'Perfil Lipidico'),
(7, 'Parcial de Orina'),
(8, 'Creatinina Suero'),
(9, 'Glicemia Basal'),
(10, 'Electrocardiograma'),
(11, 'Radiografia Torax'),
(12, 'Ecografia Abdomen'),
(13, 'Citologia Vaginal'),
(14, 'Aplic. Inyeccion IM'),
(15, 'Curacion Simple'),
(16, 'Sutura Herida'),
(17, 'Control Prenatal'),
(18, 'Vacunacion'),
(19, 'Examen Oftalmol.'),
(20, 'Audiometria'),
(21, 'TSH'),
(22, 'Antigeno Prostat.'),
(23, 'Endoscopia Digest.'),
(24, 'Colonoscopia'),
(25, 'Prueba Esfuerzo'),
(26, 'Drenaje Absceso'),
(27, 'Retiro Puntos'),
(28, 'Terapia Fisica'),
(29, 'Terapia Respirat.'),
(30, 'Mamografia'),
(31, 'Ecografia Pelvica'),
(32, 'Urocultivo'),
(33, 'Sangre Oculta Heces'),
(34, 'Nebulizacion'),
(35, 'Espirometria'),
(36, 'No aplica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `regimen`
--

CREATE TABLE `regimen` (
  `id_regimen` int(11) NOT NULL,
  `nom_reg` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `regimen`
--

INSERT INTO `regimen` (`id_regimen`, `nom_reg`) VALUES
(1, 'Contributivo'),
(2, 'Subsidiado'),
(3, 'No aplica');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre_rol`) VALUES
(1, 'Administrador'),
(2, 'Paciente'),
(3, 'Farmaceuta'),
(4, 'Médico');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_de_medicamento`
--

CREATE TABLE `tipo_de_medicamento` (
  `id_tip_medic` int(11) NOT NULL,
  `nom_tipo_medi` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_de_medicamento`
--

INSERT INTO `tipo_de_medicamento` (`id_tip_medic`, `nom_tipo_medi`) VALUES
(1, 'Analgésicos'),
(2, 'Antiinflamatorios no esteroideos (AINE)'),
(3, 'Antibióticos'),
(4, 'Antivirales'),
(5, 'Antifúngicos'),
(6, 'Antihipertensivos'),
(7, 'Antidiabéticos'),
(8, 'Antihistamínicos'),
(9, 'Broncodilatadores'),
(10, 'Antidepresivos'),
(11, 'Ansiolíticos'),
(12, 'Antipsicóticos'),
(13, 'Anticoagulantes'),
(14, 'Hipolipemiantes'),
(15, 'Diuréticos'),
(16, 'Protectores Gástricos'),
(17, 'Laxantes'),
(18, 'Corticosteroides'),
(19, 'Relajantes Musculares'),
(20, 'Quimioterápicos'),
(21, 'Vacunas'),
(22, 'Vitaminas y Suplementos'),
(23, 'Anticonceptivos Hormonales'),
(24, 'Terapia Hormonal Tiroidea'),
(25, 'Anticonvulsivantes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_enfermedades`
--

CREATE TABLE `tipo_enfermedades` (
  `id_tipo_enfer` int(11) NOT NULL,
  `tipo_enfermer` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_enfermedades`
--

INSERT INTO `tipo_enfermedades` (`id_tipo_enfer`, `tipo_enfermer`) VALUES
(1, 'Respiratorias'),
(2, 'Cardiovasculares'),
(3, 'Neurologicas'),
(4, 'Gastrointestinales'),
(5, 'Endocrinas'),
(6, 'Renales y Urológicas'),
(7, 'Dermatologicas'),
(8, 'Musculoesqueléticas y Reumatológicas'),
(9, 'Hematológicas'),
(10, 'Infecciosas y Parasitarias'),
(11, 'Oncológicas'),
(12, 'Mentales y del Comportamiento'),
(13, 'Genéticas y Congénitas'),
(14, 'Autoinmunes'),
(15, 'Traumatismos y Envenenamientos'),
(16, 'Alérgicas e Inmunológicas'),
(17, 'Oftalmológicas'),
(18, 'Otorrinolaringológicas'),
(19, 'Nutricionales y Metabólicas'),
(20, 'Ginecológicas y Obstétricas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_identificacion`
--

CREATE TABLE `tipo_identificacion` (
  `id_tipo_doc` int(11) NOT NULL,
  `nom_doc` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_identificacion`
--

INSERT INTO `tipo_identificacion` (`id_tipo_doc`, `nom_doc`) VALUES
(1, 'Cedula de Ciudadania'),
(2, 'Tarjeta de Identidad'),
(3, 'Registro Civil de Nacimiento'),
(4, 'Cedula de Extranjeria'),
(5, 'Pasaporte');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turno_ent_medic`
--

CREATE TABLE `turno_ent_medic` (
  `id_turno_ent` int(11) NOT NULL,
  `fecha_entreg` date NOT NULL,
  `hora_entreg` int(11) NOT NULL,
  `id_historia` int(11) NOT NULL,
  `id_est` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turno_examen`
--

CREATE TABLE `turno_examen` (
  `id_turno_exa` int(11) NOT NULL,
  `fech_exam` date NOT NULL,
  `hora_exam` int(11) NOT NULL,
  `id_historia` int(11) NOT NULL,
  `id_est` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `doc_usu` bigint(20) NOT NULL,
  `id_tipo_doc` int(11) NOT NULL,
  `nom_usu` varchar(100) NOT NULL,
  `fecha_nac` date NOT NULL,
  `tel_usu` varchar(12) NOT NULL,
  `correo_usu` varchar(150) NOT NULL,
  `id_barrio` int(11) NOT NULL,
  `direccion_usu` varchar(200) NOT NULL,
  `foto_usu` varchar(500) DEFAULT NULL,
  `pass` varchar(200) NOT NULL,
  `id_gen` int(11) NOT NULL,
  `id_est` int(11) NOT NULL,
  `id_especialidad` int(11) NOT NULL,
  `id_rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`doc_usu`, `id_tipo_doc`, `nom_usu`, `fecha_nac`, `tel_usu`, `correo_usu`, `id_barrio`, `direccion_usu`, `foto_usu`, `pass`, `id_gen`, `id_est`, `id_especialidad`, `id_rol`) VALUES
(1111, 1, 'Daniel Montealegre', '2006-02-27', '3135936601', 'danielmontealegre40@gmail.com', 10, 'Calle 145', NULL, '$2y$10$T1rtQ28NiqkyfQdb6PllCuPbwL5fu5s0p8uMfh6IWA4VlWPBU7LJW', 1, 1, 46, 1),
(2222, 1, 'Asmy Murillo', '2007-02-23', '3133555522', 'asly@gmail.com', 39, 'calle 3', NULL, '$2y$10$gcRvCkWk4CjH2PdZa0Tl2e4sOdhP.PnKoJG2iwWorbkh0AMhuwX6W', 2, 1, 46, 2),
(3333, 1, 'Daniel Rojas', '2006-02-27', '3135936601', 'daniel12@gmail.com', 26, 'calle 456', NULL, '$2y$10$CaWBC8YaQ/iG71igBuzqW.7grrT5XlPfGSpiSrRq4ir/cOumS5OCK', 1, 1, 46, 3),
(4444, 1, 'Brian Rocha', '2007-02-10', '2145514', 'Brian@gmail.com', 10, 'calle 45623', NULL, '$2y$10$wlkG.K/SzoqPy9RqVazsc.B/aP.jjM2n3d75Jl5skA6piE1sFwtpG', 1, 1, 13, 4);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `afiliados`
--
ALTER TABLE `afiliados`
  ADD PRIMARY KEY (`id_afiliacion`),
  ADD KEY `doc_afiliadiado` (`doc_afiliadiado`),
  ADD KEY `id_eps` (`id_eps`),
  ADD KEY `id_regimen` (`id_regimen`),
  ADD KEY `id_arl` (`id_arl`);

--
-- Indices de la tabla `arl`
--
ALTER TABLE `arl`
  ADD PRIMARY KEY (`id_arl`);

--
-- Indices de la tabla `asignacion_medico`
--
ALTER TABLE `asignacion_medico`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `doc_medico` (`doc_medico`),
  ADD KEY `nit_ips` (`nit_ips`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indices de la tabla `barrio`
--
ALTER TABLE `barrio`
  ADD PRIMARY KEY (`id_barrio`),
  ADD KEY `id_mun` (`id_mun`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id_cita`),
  ADD KEY `doc_pac` (`doc_pac`),
  ADD KEY `doc_med` (`doc_med`),
  ADD KEY `nit_IPS` (`nit_IPS`),
  ADD KEY `id_est` (`id_est`);

--
-- Indices de la tabla `departamento`
--
ALTER TABLE `departamento`
  ADD PRIMARY KEY (`id_dep`);

--
-- Indices de la tabla `detalles_enfermedades_tipo_enfermedades`
--
ALTER TABLE `detalles_enfermedades_tipo_enfermedades`
  ADD PRIMARY KEY (`id_detalle_enfer`),
  ADD KEY `id_enferme` (`id_enferme`),
  ADD KEY `id_tipo_enfer` (`id_tipo_enfer`);

--
-- Indices de la tabla `detalles_histo_clini`
--
ALTER TABLE `detalles_histo_clini`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_historia` (`id_historia`),
  ADD KEY `id_diagnostico` (`id_diagnostico`),
  ADD KEY `id_enferme` (`id_enferme`),
  ADD KEY `id_medicam` (`id_medicam`),
  ADD KEY `id_proced` (`id_proced`);

--
-- Indices de la tabla `detalle_eps_farm`
--
ALTER TABLE `detalle_eps_farm`
  ADD PRIMARY KEY (`id_eps_farm`),
  ADD KEY `nit_eps` (`nit_eps`),
  ADD KEY `nit_farm` (`nit_farm`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indices de la tabla `detalle_eps_ips`
--
ALTER TABLE `detalle_eps_ips`
  ADD PRIMARY KEY (`id_eps_ips`),
  ADD KEY `nit_eps` (`nit_eps`),
  ADD KEY `nit_ips` (`nit_ips`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indices de la tabla `diagnostico`
--
ALTER TABLE `diagnostico`
  ADD PRIMARY KEY (`id_diagnos`);

--
-- Indices de la tabla `enfermedades`
--
ALTER TABLE `enfermedades`
  ADD PRIMARY KEY (`id_enferme`),
  ADD KEY `id_tipo_enfer` (`id_tipo_enfer`);

--
-- Indices de la tabla `eps`
--
ALTER TABLE `eps`
  ADD PRIMARY KEY (`nit_eps`);

--
-- Indices de la tabla `especialidad`
--
ALTER TABLE `especialidad`
  ADD PRIMARY KEY (`id_espe`);

--
-- Indices de la tabla `estado`
--
ALTER TABLE `estado`
  ADD PRIMARY KEY (`id_est`);

--
-- Indices de la tabla `farmacias`
--
ALTER TABLE `farmacias`
  ADD PRIMARY KEY (`nit_farm`);

--
-- Indices de la tabla `genero`
--
ALTER TABLE `genero`
  ADD PRIMARY KEY (`id_gen`);

--
-- Indices de la tabla `historia_clinica`
--
ALTER TABLE `historia_clinica`
  ADD PRIMARY KEY (`id_historia`),
  ADD KEY `id_cita` (`id_cita`);

--
-- Indices de la tabla `horario_examen`
--
ALTER TABLE `horario_examen`
  ADD PRIMARY KEY (`id_horario_exan`),
  ADD KEY `horario_examen_ibfk_1` (`meridiano`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indices de la tabla `horario_farm`
--
ALTER TABLE `horario_farm`
  ADD PRIMARY KEY (`id_horario_farm`),
  ADD KEY `id_estado` (`id_estado`),
  ADD KEY `meridiano` (`meridiano`);

--
-- Indices de la tabla `horario_medico`
--
ALTER TABLE `horario_medico`
  ADD PRIMARY KEY (`id_horario_med`),
  ADD KEY `doc_medico` (`doc_medico`),
  ADD KEY `id_estado` (`id_estado`);

--
-- Indices de la tabla `inventario_farmacia`
--
ALTER TABLE `inventario_farmacia`
  ADD PRIMARY KEY (`id_inventario`),
  ADD UNIQUE KEY `uk_medicamento_farmacia` (`id_medicamento`,`nit_farm`),
  ADD KEY `nit_farm` (`nit_farm`);

--
-- Indices de la tabla `ips`
--
ALTER TABLE `ips`
  ADD PRIMARY KEY (`Nit_IPS`);

--
-- Indices de la tabla `medicamentos`
--
ALTER TABLE `medicamentos`
  ADD PRIMARY KEY (`id_medicamento`),
  ADD KEY `id_tipo_medic` (`id_tipo_medic`);

--
-- Indices de la tabla `meridiano`
--
ALTER TABLE `meridiano`
  ADD PRIMARY KEY (`id_periodo`);

--
-- Indices de la tabla `municipio`
--
ALTER TABLE `municipio`
  ADD PRIMARY KEY (`id_mun`),
  ADD KEY `id_dep` (`id_dep`);

--
-- Indices de la tabla `procedimientos`
--
ALTER TABLE `procedimientos`
  ADD PRIMARY KEY (`id_proced`);

--
-- Indices de la tabla `regimen`
--
ALTER TABLE `regimen`
  ADD PRIMARY KEY (`id_regimen`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `tipo_de_medicamento`
--
ALTER TABLE `tipo_de_medicamento`
  ADD PRIMARY KEY (`id_tip_medic`);

--
-- Indices de la tabla `tipo_enfermedades`
--
ALTER TABLE `tipo_enfermedades`
  ADD PRIMARY KEY (`id_tipo_enfer`);

--
-- Indices de la tabla `tipo_identificacion`
--
ALTER TABLE `tipo_identificacion`
  ADD PRIMARY KEY (`id_tipo_doc`);

--
-- Indices de la tabla `turno_ent_medic`
--
ALTER TABLE `turno_ent_medic`
  ADD PRIMARY KEY (`id_turno_ent`),
  ADD KEY `hora_entreg` (`hora_entreg`),
  ADD KEY `id_historia` (`id_historia`),
  ADD KEY `id_est` (`id_est`);

--
-- Indices de la tabla `turno_examen`
--
ALTER TABLE `turno_examen`
  ADD PRIMARY KEY (`id_turno_exa`),
  ADD KEY `hora_exam` (`hora_exam`),
  ADD KEY `id_historia` (`id_historia`),
  ADD KEY `id_est` (`id_est`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`doc_usu`),
  ADD KEY `id_tipo_doc` (`id_tipo_doc`),
  ADD KEY `id_barrio` (`id_barrio`),
  ADD KEY `id_gen` (`id_gen`),
  ADD KEY `id_est` (`id_est`),
  ADD KEY `id_especialidad` (`id_especialidad`),
  ADD KEY `id_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `afiliados`
--
ALTER TABLE `afiliados`
  MODIFY `id_afiliacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `arl`
--
ALTER TABLE `arl`
  MODIFY `id_arl` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `asignacion_medico`
--
ALTER TABLE `asignacion_medico`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `barrio`
--
ALTER TABLE `barrio`
  MODIFY `id_barrio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id_cita` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalles_enfermedades_tipo_enfermedades`
--
ALTER TABLE `detalles_enfermedades_tipo_enfermedades`
  MODIFY `id_detalle_enfer` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalles_histo_clini`
--
ALTER TABLE `detalles_histo_clini`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_eps_farm`
--
ALTER TABLE `detalle_eps_farm`
  MODIFY `id_eps_farm` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_eps_ips`
--
ALTER TABLE `detalle_eps_ips`
  MODIFY `id_eps_ips` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `diagnostico`
--
ALTER TABLE `diagnostico`
  MODIFY `id_diagnos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `enfermedades`
--
ALTER TABLE `enfermedades`
  MODIFY `id_enferme` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=115;

--
-- AUTO_INCREMENT de la tabla `especialidad`
--
ALTER TABLE `especialidad`
  MODIFY `id_espe` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de la tabla `estado`
--
ALTER TABLE `estado`
  MODIFY `id_est` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `genero`
--
ALTER TABLE `genero`
  MODIFY `id_gen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `historia_clinica`
--
ALTER TABLE `historia_clinica`
  MODIFY `id_historia` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `horario_examen`
--
ALTER TABLE `horario_examen`
  MODIFY `id_horario_exan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `horario_farm`
--
ALTER TABLE `horario_farm`
  MODIFY `id_horario_farm` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT de la tabla `horario_medico`
--
ALTER TABLE `horario_medico`
  MODIFY `id_horario_med` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_farmacia`
--
ALTER TABLE `inventario_farmacia`
  MODIFY `id_inventario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=365;

--
-- AUTO_INCREMENT de la tabla `medicamentos`
--
ALTER TABLE `medicamentos`
  MODIFY `id_medicamento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT de la tabla `meridiano`
--
ALTER TABLE `meridiano`
  MODIFY `id_periodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `procedimientos`
--
ALTER TABLE `procedimientos`
  MODIFY `id_proced` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `regimen`
--
ALTER TABLE `regimen`
  MODIFY `id_regimen` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `tipo_de_medicamento`
--
ALTER TABLE `tipo_de_medicamento`
  MODIFY `id_tip_medic` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de la tabla `tipo_enfermedades`
--
ALTER TABLE `tipo_enfermedades`
  MODIFY `id_tipo_enfer` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `tipo_identificacion`
--
ALTER TABLE `tipo_identificacion`
  MODIFY `id_tipo_doc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `turno_ent_medic`
--
ALTER TABLE `turno_ent_medic`
  MODIFY `id_turno_ent` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `turno_examen`
--
ALTER TABLE `turno_examen`
  MODIFY `id_turno_exa` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `afiliados`
--
ALTER TABLE `afiliados`
  ADD CONSTRAINT `afiliados_ibfk_1` FOREIGN KEY (`doc_afiliadiado`) REFERENCES `usuarios` (`doc_usu`),
  ADD CONSTRAINT `afiliados_ibfk_2` FOREIGN KEY (`id_eps`) REFERENCES `eps` (`nit_eps`),
  ADD CONSTRAINT `afiliados_ibfk_3` FOREIGN KEY (`id_regimen`) REFERENCES `regimen` (`id_regimen`),
  ADD CONSTRAINT `afiliados_ibfk_4` FOREIGN KEY (`id_arl`) REFERENCES `arl` (`id_arl`);

--
-- Filtros para la tabla `asignacion_medico`
--
ALTER TABLE `asignacion_medico`
  ADD CONSTRAINT `asignacion_medico_ibfk_1` FOREIGN KEY (`doc_medico`) REFERENCES `usuarios` (`doc_usu`),
  ADD CONSTRAINT `asignacion_medico_ibfk_2` FOREIGN KEY (`nit_ips`) REFERENCES `ips` (`Nit_IPS`),
  ADD CONSTRAINT `asignacion_medico_ibfk_3` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_est`);

--
-- Filtros para la tabla `barrio`
--
ALTER TABLE `barrio`
  ADD CONSTRAINT `barrio_ibfk_1` FOREIGN KEY (`id_mun`) REFERENCES `municipio` (`id_mun`);

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`doc_pac`) REFERENCES `usuarios` (`doc_usu`),
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`doc_med`) REFERENCES `usuarios` (`doc_usu`),
  ADD CONSTRAINT `citas_ibfk_3` FOREIGN KEY (`nit_IPS`) REFERENCES `ips` (`Nit_IPS`),
  ADD CONSTRAINT `citas_ibfk_4` FOREIGN KEY (`id_est`) REFERENCES `estado` (`id_est`);

--
-- Filtros para la tabla `detalles_enfermedades_tipo_enfermedades`
--
ALTER TABLE `detalles_enfermedades_tipo_enfermedades`
  ADD CONSTRAINT `detalles_enfermedades_tipo_enfermedades_ibfk_1` FOREIGN KEY (`id_enferme`) REFERENCES `enfermedades` (`id_enferme`),
  ADD CONSTRAINT `detalles_enfermedades_tipo_enfermedades_ibfk_2` FOREIGN KEY (`id_tipo_enfer`) REFERENCES `tipo_enfermedades` (`id_tipo_enfer`);

--
-- Filtros para la tabla `detalles_histo_clini`
--
ALTER TABLE `detalles_histo_clini`
  ADD CONSTRAINT `detalles_histo_clini_ibfk_1` FOREIGN KEY (`id_historia`) REFERENCES `historia_clinica` (`id_historia`),
  ADD CONSTRAINT `detalles_histo_clini_ibfk_2` FOREIGN KEY (`id_diagnostico`) REFERENCES `diagnostico` (`id_diagnos`),
  ADD CONSTRAINT `detalles_histo_clini_ibfk_3` FOREIGN KEY (`id_enferme`) REFERENCES `enfermedades` (`id_enferme`),
  ADD CONSTRAINT `detalles_histo_clini_ibfk_4` FOREIGN KEY (`id_medicam`) REFERENCES `medicamentos` (`id_medicamento`),
  ADD CONSTRAINT `detalles_histo_clini_ibfk_5` FOREIGN KEY (`id_proced`) REFERENCES `procedimientos` (`id_proced`);

--
-- Filtros para la tabla `detalle_eps_farm`
--
ALTER TABLE `detalle_eps_farm`
  ADD CONSTRAINT `detalle_eps_farm_ibfk_1` FOREIGN KEY (`nit_eps`) REFERENCES `eps` (`nit_eps`),
  ADD CONSTRAINT `detalle_eps_farm_ibfk_2` FOREIGN KEY (`nit_farm`) REFERENCES `farmacias` (`nit_farm`),
  ADD CONSTRAINT `detalle_eps_farm_ibfk_3` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_est`);

--
-- Filtros para la tabla `detalle_eps_ips`
--
ALTER TABLE `detalle_eps_ips`
  ADD CONSTRAINT `detalle_eps_ips_ibfk_1` FOREIGN KEY (`nit_eps`) REFERENCES `eps` (`nit_eps`),
  ADD CONSTRAINT `detalle_eps_ips_ibfk_2` FOREIGN KEY (`nit_ips`) REFERENCES `ips` (`Nit_IPS`),
  ADD CONSTRAINT `detalle_eps_ips_ibfk_3` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_est`);

--
-- Filtros para la tabla `enfermedades`
--
ALTER TABLE `enfermedades`
  ADD CONSTRAINT `enfermedades_ibfk_1` FOREIGN KEY (`id_tipo_enfer`) REFERENCES `tipo_enfermedades` (`id_tipo_enfer`);

--
-- Filtros para la tabla `historia_clinica`
--
ALTER TABLE `historia_clinica`
  ADD CONSTRAINT `historia_clinica_ibfk_1` FOREIGN KEY (`id_cita`) REFERENCES `citas` (`id_cita`);

--
-- Filtros para la tabla `horario_examen`
--
ALTER TABLE `horario_examen`
  ADD CONSTRAINT `horario_examen_ibfk_1` FOREIGN KEY (`meridiano`) REFERENCES `meridiano` (`id_periodo`),
  ADD CONSTRAINT `horario_examen_ibfk_2` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_est`);

--
-- Filtros para la tabla `horario_farm`
--
ALTER TABLE `horario_farm`
  ADD CONSTRAINT `horario_farm_ibfk_1` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_est`),
  ADD CONSTRAINT `horario_farm_ibfk_2` FOREIGN KEY (`meridiano`) REFERENCES `meridiano` (`id_periodo`);

--
-- Filtros para la tabla `horario_medico`
--
ALTER TABLE `horario_medico`
  ADD CONSTRAINT `horario_medico_ibfk_1` FOREIGN KEY (`doc_medico`) REFERENCES `usuarios` (`doc_usu`),
  ADD CONSTRAINT `horario_medico_ibfk_2` FOREIGN KEY (`id_estado`) REFERENCES `estado` (`id_est`);

--
-- Filtros para la tabla `inventario_farmacia`
--
ALTER TABLE `inventario_farmacia`
  ADD CONSTRAINT `inventario_farmacia_ibfk_1` FOREIGN KEY (`id_medicamento`) REFERENCES `medicamentos` (`id_medicamento`),
  ADD CONSTRAINT `inventario_farmacia_ibfk_2` FOREIGN KEY (`nit_farm`) REFERENCES `farmacias` (`nit_farm`);

--
-- Filtros para la tabla `medicamentos`
--
ALTER TABLE `medicamentos`
  ADD CONSTRAINT `medicamentos_ibfk_1` FOREIGN KEY (`id_tipo_medic`) REFERENCES `tipo_de_medicamento` (`id_tip_medic`);

--
-- Filtros para la tabla `municipio`
--
ALTER TABLE `municipio`
  ADD CONSTRAINT `municipio_ibfk_1` FOREIGN KEY (`id_dep`) REFERENCES `departamento` (`id_dep`);

--
-- Filtros para la tabla `turno_ent_medic`
--
ALTER TABLE `turno_ent_medic`
  ADD CONSTRAINT `turno_ent_medic_ibfk_1` FOREIGN KEY (`hora_entreg`) REFERENCES `horario_farm` (`id_horario_farm`),
  ADD CONSTRAINT `turno_ent_medic_ibfk_2` FOREIGN KEY (`id_historia`) REFERENCES `historia_clinica` (`id_historia`),
  ADD CONSTRAINT `turno_ent_medic_ibfk_3` FOREIGN KEY (`id_est`) REFERENCES `estado` (`id_est`);

--
-- Filtros para la tabla `turno_examen`
--
ALTER TABLE `turno_examen`
  ADD CONSTRAINT `turno_examen_ibfk_1` FOREIGN KEY (`hora_exam`) REFERENCES `horario_examen` (`id_horario_exan`),
  ADD CONSTRAINT `turno_examen_ibfk_2` FOREIGN KEY (`id_historia`) REFERENCES `historia_clinica` (`id_historia`),
  ADD CONSTRAINT `turno_examen_ibfk_3` FOREIGN KEY (`id_est`) REFERENCES `estado` (`id_est`);

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`id_tipo_doc`) REFERENCES `tipo_identificacion` (`id_tipo_doc`),
  ADD CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`id_barrio`) REFERENCES `barrio` (`id_barrio`),
  ADD CONSTRAINT `usuarios_ibfk_3` FOREIGN KEY (`id_gen`) REFERENCES `genero` (`id_gen`),
  ADD CONSTRAINT `usuarios_ibfk_4` FOREIGN KEY (`id_est`) REFERENCES `estado` (`id_est`),
  ADD CONSTRAINT `usuarios_ibfk_5` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidad` (`id_espe`),
  ADD CONSTRAINT `usuarios_ibfk_6` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
