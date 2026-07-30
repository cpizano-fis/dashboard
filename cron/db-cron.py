# -*- coding: utf-8 -*-

from io import BytesIO
import xlsxwriter
import psycopg2
import calendar
import locale
import sys
from datetime import datetime, timedelta

#Dónde se guardan los archivos de excel generados para descagar
XLXS_PATH = "/var/www/html/xlsx/"
#Datos de la base de datos (Implementar como un cargue de json para php y python)
DB_HOST = "fis-dashboard-test.cwkvhvjuxg8i.us-east-1.rds.amazonaws.com"
DB_DATABASE = "fis-dashboard-test"

DB_HOST_FIS = "db-sifis-ec.cwkvhvjuxg8i.us-east-1.rds.amazonaws.com"
DB_DATABASE_FIS = "fis"

SUBC = {
    "PRODUCTO": 0,
    "PROYECTOS": 1,
    "DIGITAL BUSINESS": 2,
    "AMBIENTAL": 3,
    "RELACIONADAS": 4,
    "SOCIOS PROYECTOS": 5,
    "SOCIOS PRODUCTOS": 6,
    "SERVICIO DE LOGISTICA": 7,
    "SERVICIOS PRESTADOS": 8,
    "CENTRO TÉCNICO": 9,
    "SERVICIOS VARIOS": 10
}

GRUPO = {
    33444: "COLINTEGRAL CONSULTING S.A.S",
    46483: "COLINTEGRAL CONSULTING S.A.S",
    34540: "COLINTEGRAL CONSULTING S.A.S",
    1: "ECUAINTEGRAL CONSULTING SA",
    21030: "INTEROPTIC FIBER INTERNET CON FIBRA OPTICA CIA. LTDA.",
    31892: "SPEEDFIBER CIA LTDA"
}

SOCIO = {
    43328: "CUARTAS DE LOAIZA MARTA ISABEL",
    20647: "MARTA ISABEL CUARTAS DE LOAIZA",
    46907: "LOAIZA CUARTAS KARLA PAMELA",
    21146: "LOAIZA CUARTAS KARLA PAMELA",
    24012: "LOAIZA CUARTAS PAMELA KARLA",
    34596: "PAMELA LOAIZA",
    32622: "PAMELA LOAIZA",
    24524: "LOAIZA CUARTAS NATALIA",
    21147: "LOAIZA CUARTAS NATALIA",
    36704: "LOAIZA NATALIA",
    24537: "Natalia Loaiza",
    94: "Natalia Loaiza Cuartas",
    24442: "ISOLDA LOAIZA",
    47351: "LOAIZA CUARTAS BIBIANA ISOLDA",
    21144: "LOAIZA CUARTAS ISOLDA BIBIANA",
    21148: "LOAIZA CUARTAS ANDREA PAOLA",
    43486: "LOAIZA CUARTAS PAOLA ANDREA",
    47118: "LOAIZA CUARTAS PAOLA ANDREA",
    43626: "Paola Loaiza",
    24430: "Paola Loaiza",
    96: "JUAN FELIPE LOAIZA",
    21145: "LOAIZA CUARTAS JUAN FELIPE",
    48920: "LOAIZA CUARTAS JUAN FELIPE"
}

def to_datetime(date):
	try:
		d = datetime.strptime(date, '%Y-%m-%d')
	except Exception as e:
		d = datetime.strptime(date.split(' ')[0], '%Y-%m-%d')
	return d

# agregar diferenciador de tipo de consulta
def get_sql_date_month_range(hoy):
    _, lday = calendar.monthrange(hoy.year, hoy.month)
    return "'" + hoy.strftime("%Y-%m-01") + "' AND '" + hoy.strftime("%Y-%m-") + str(lday) + "'"

def get_sql_date_week_range(hoy):
    start_of_week = hoy - timedelta(days=hoy.weekday())
    end_of_week = start_of_week + timedelta(days=6)
    return "'" + start_of_week.strftime("%Y-%m-%d") + "' AND '" + end_of_week.strftime("%Y-%m-%d") + "'"

# tipo: integer indica si es Ventas, Compras etc.
def get_sql(tipo, y, m):
    #hoy = datetime.now()
    hoy = datetime(y, m, 1, 12, 30, 0)
    #print(get_sql_date_month_range(hoy))
    #print(get_sql_date_week_range(hoy))
    sql = """SELECT i.id, i.type, i.invoice_number, c.name, i.date, t.type ptype, t.default_code, t.name pname, l.quantity, 
        m.name uomname, l.price_unit, l.discount, up.name uname, ln.name lname, sl.name slname, i.state, l.partner_id, i.user_id
        FROM account_invoice_line l
        INNER JOIN account_invoice i ON i.id = l.invoice_id
        INNER JOIN product_product p ON p.id = l.product_id
        INNER JOIN product_template t ON t.id = p.product_tmpl_id
        INNER JOIN res_partner c ON c.id = i.partner_id
        INNER JOIN product_uom m ON m.id = l.uom_id
        INNER JOIN res_users u ON u.id = i.user_id
        INNER JOIN res_partner up ON up.id = u.partner_id
        LEFT JOIN sales_fis_lineas_negocio ln ON ln.id = t.linea_negocio
        LEFT JOIN sales_fis_sublineas_negocio sl ON sl.id = t.sublinea_negocio_id
        WHERE i.date BETWEEN """
    sql += get_sql_date_month_range(hoy) 
    sql += " AND i.type IN ('out_invoice', 'out_refund') AND i.state IN ('open', 'paid') ORDER BY i.date, i.id, l.id"
    return sql

def get_wb_names(tipo, y, m):
    locale.setlocale(locale.LC_TIME, 'es_EC.UTF-8')
    #dt = datetime.now()
    dt = datetime(y, m, 1, 12, 30, 0)
    sn = "VENTAS-" + dt.strftime('%Y%m-%B')
    fn = XLXS_PATH + sn + ".xlsx"
    return sn, fn

def get_data(dbtype, y, m):
    # 1. Conexión a la base de datos
    #conexion = psycopg2.connect(
    #    host=DB_HOST,
    #    database=DB_DATABASE,
    #    user="postgres",
    #    password="F!sdbtest-2026",
    #    port="5432"
    #)
    conexion = psycopg2.connect(
        host=DB_HOST_FIS,
        database=DB_DATABASE_FIS,
        user="odoousr",
        password="FISDBecpw",
        port="5432"
    )
    # 2. Creación del cursor
    cursor = conexion.cursor()
    # 3. Ejecución de la consulta
    sql = get_sql(dbtype, y, m)
    cursor.execute(sql)
    # 4. Obtención e impresión de resultados
    res = cursor.fetchall()
    # 5. Cierre de cursores y conexión
    cursor.close()
    conexion.close()
    return res

def get_titles(tipo):
    titles = ["Numero factura", "Empresa", "Fecha", "Tipo PS", "Codigo", "Descripción", 
	"Cantidad", "Unidad de medida", "Precio unitario", "Desc. (%)", "Venta Neta", 
	"Comercial", "Linea Negocio", "SUBCATEGORIA", "PRODUCTO", "PROYECTOS", "DIGITAL BUSINESS", 
	"AMBIENTAL", "RELACIONADAS", "SOCIOS PROYECTOS", "SOCIOS PRODUCTOS", "SERVICIO DE LOGISTICA", 
	"SERVICIOS PRESTADOS", "CENTRO TÉCNICO", "SERVICIOS VARIOS"]
    return titles

# 0: i.id
# 1: i.type
# 2: i.invoice_number
# 3: c.name
# 4: i.date
# 5: t.type
# 6: t.default_code
# 7: t.name
# 8: l.quantity
# 9: m.name
#10: l.price_unit
#11: l.discount
#12: up.name
#13: ln.name
#14: sl.name
#15: i.state
#16: l.partner_id
#17: i.user_id

# account_invoice i
# product_product p
# product_template t
# res_partner c compañia
# product_uom m
# res_users u
# res_partner up usuario
# sales_fis_lineas_negocio ln
# sales_fis_sublineas_negocio sl

try:
    if len(sys.argv) > 1:
        empresa = sys.argv[1]
        tipo = sys.argv[2]
        anio = sys.argv[3]
        mes = sys.argv[4]
    else:
        dt = datetime.now()
        empresa = "ECUAINTEGRAL"
        tipo = 0 # VENTAS
        anio = dt.year
        mes = dt.month
    resultados = get_data(tipo, anio, mes)
    titles = get_titles(tipo)
    s_name, f_name = get_wb_names(tipo, anio, mes)
    #file_data = BytesIO()
    #workbook = xlsxwriter.Workbook(file_data, {})
    workbook = xlsxwriter.Workbook(f_name)
    bold_format = workbook.add_format({'bold': True})
    date_format = workbook.add_format({'num_format': 'yyyy-mm-dd'})
    #    money_format = workbook.add_format({'num_format': '$#,##0.00'})
    # Se debe implementar el borrado de archivos ?
    sheet = workbook.add_worksheet(s_name)

    cmax = len(titles)
    # Títulos
    for col in range(cmax):
        sheet.write(0, col, titles[col], bold_format)
        #print(titles[col])
    n = 1
    prev_f = 0
    p = 2
    for fila in resultados:
        if prev_f != fila[0]: # Cambio valores según factura o nota crédito
            prev_f = fila[0]
            if fila[1] == 'out_invoice':
                tipo_f = "FV "
                multip = 1
            else:
                tipo_f = "NC "
                multip = -1
        sheet.write(n, 0, tipo_f + fila[2])
        for c in range(1, 13):
            if c == 10:
                # cantidad * (1 - descuento / 100) * precio_unitario
                valor = multip * fila[8] * (1 - fila[11] / 100) * fila[10]
                p = 1
                ixpy = None
                # relacionada si 
                if fila[16] in GRUPO:
                    ixpy = "RELACIONADAS"
                if fila[16] in SOCIO:
                    ixpy = "SOCIOS PRODUCTOS"
                if ixpy is None:
                    ixpy = "PRODUCTO" if fila[13] is None else fila[13]
                if ixpy == "PROYECTOS" and fila[17] in SOCIO:
                    ixpy = "SOCIOS PROYECTOS"
                #print("Fila " + str(n) + ": " + ixpy + " -> " + str(fila[13]))
                ixsc = 0 if not ixpy in SUBC else SUBC[ixpy]
                #if ixsc is None: 
                #    ixsb = 0
                #sheet.write(n, 13, fila[14])
                sheet.write(n, 13, ixpy)
                sheet.write(n, 14 + ixsc, valor)
            else:
                valor = fila[c + p]
            if c != 2:
                sheet.write(n, c, valor)
            else: 
                sheet.write_datetime(n, c, valor, date_format)

        n += 1
        p = 2
    workbook.close()

except psycopg2.Error as e:
    print("Ocurrio un error al conectar a la base de datos:", e)
