//Powered By HaZuEr.Ing
//Version:08072025
// Solicitar los números de seguimiento mediante un prompt
const input = prompt("👾 Ingresa los números de guía Cainiao [📦]:");
// Procesar el input para crear el array
const trackingNumbers = input
    ? input.split('\n')               // Dividir por saltos de línea
        .map(num => num.trim())    // Limpiar espacios
        .filter(num => num !== '') // Eliminar líneas vacías
    : []; // Si no se ingresa nada, array vacío
const color = prompt(`
👾 Color (elige un número) [🎨]:
---------------------------------
🔴[1] red    🟢[3] green
💙[2] blue   ⚫[4] black
---------------------------------`).trim().toLowerCase() || "4";

// Mapear números a nombres de color
const colorMapNumber = {
    '1': 'red',
    '2': 'blue',
    '3': 'green',
    '4': 'black'
};
const colorMap = {
    '1': '🔴',
    '2': '💙',
    '3': '🟢',
    '4': '⚫'
};
// Validación y asignación del color
const colorFinal = colorMapNumber[color] || "black";
// Solicitar ubicación con opciones claras
const id_location = prompt(`
👾 Ingresa el ID de ubicación [📍]:
1 - TQL, 2 - ZAC
3 - JTL, 4 - TLZ`
) || 1;

// Asignar usuario según ubicación
let id_user;

if (id_location == 1) {
    id_user = 2; // Karen
} else if (id_location == 3 || id_location == 4) {
    id_user = 9; // Jessica
} else {
    id_user = 4; // Josue
}

// Generar mensaje de confirmación
const guiaInicial = trackingNumbers[0] || "N/A";
const guiaFinal = trackingNumbers[trackingNumbers.length - 1] || "N/A";
const totalGuias = trackingNumbers.length;

const locationMap = {
    1: "TQL",
    2: "ZAC",
    3: "JTL",
    4: "TLZ"
};

const mensajeConfirmacion = `
¿👾 Los datos son correctos? [⚙️]:
---------------------------------
📦 CAINIAO
🔢 Total de guías: ${totalGuias}
👉 Guía inicial: ${guiaInicial}
👉 Guía final: ${guiaFinal}
---------------------------------
🎨 Color: ${colorMap[color]}
📍 Ubicación: ${locationMap[id_location] || "DESCONOCIDA"}`;

// Mostrar alerta de confirmación
const isConfirmed = confirm(mensajeConfirmacion);

// Endpoint function
async function enviarDatos(resultado) {
    try {
        const endpoint = "https://paqueterialospinos.com/controllers/puppeteer.php";
        console.log(`📤 Enviando datos de ${resultado.tracking} al endpoint paqueterialospinos`);
        const response = await page.evaluate(async (url, data) => {
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify(data)
            });
            return await response.json();
        }, endpoint, resultado);
        console.log("✅ Respuesta del servidor:", response);
        return response;
    } catch (error) {
        console.error("❌ Error al enviar datos:", error);
        return { success: "false", message: "Error de red o excepción" };
    }
}

// Array to store all results
const resultados = [];
let contador = 0;
const totalElementos = trackingNumbers.length;

if (isConfirmed) {
    try {
        const trackingString = trackingNumbers.join(',');
        const textareaSelector = 'textarea[placeholder="Por favor ingrese"]';
        await page.waitForSelector(textareaSelector, { visible: true });
        await page.evaluate((selector, value) => {
            const textarea = document.querySelector(selector);
            // Setter nativo (clave para React)
            const nativeSetter = Object.getOwnPropertyDescriptor(
                window.HTMLTextAreaElement.prototype,
                'value'
            ).set;

            // 1️ Limpiar correctamente
            nativeSetter.call(textarea, '');
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.dispatchEvent(new Event('change', { bubbles: true }));

            // 2️ Insertar nuevo valor
            nativeSetter.call(textarea, value);
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            textarea.dispatchEvent(new Event('change', { bubbles: true }));

        }, textareaSelector, trackingString);

        //console.log("✅ Campo limpiado + tracking insertado");

        // Esperar a que React procese
        await page.waitForTimeout(500);
        // 3️ Click en Consulta
        await page.evaluate(() => {
            const btn = [...document.querySelectorAll('button')]
                .find(b => b.innerText.includes('Consulta'));
            if (btn) btn.click();
        });

        await page.waitForTimeout(500);
        console.log("🚀 Ready ..");
        // 🔥 Ajustar paginación a 200
        try {
            console.log("⚙️ Ajustando paginación a 200...");
            // 1️ Abrir el selector (el que muestra "20")
            await page.evaluate(() => {
                const selector = document.querySelector('.cn-next-pagination-size-selector-dropdown');
                if (selector) selector.click();
            });
            await page.waitForTimeout(500);
            // 2️ Seleccionar opción 200
            await page.evaluate(() => {
                const opciones = [...document.querySelectorAll('.cn-next-menu-item')];
                const opcion200 = opciones.find(el => el.innerText.trim() === '200');
                if (opcion200) opcion200.click();
            });
            console.log("✅ Paginación ajustada a 200");
            // Esperar recarga de tabla
            await page.waitForTimeout(1000);

        } catch (error) {
            console.log("⚠️ No se pudo ajustar la paginación:", error.message);
        }

        const filas = await page.$$('table tbody tr');
        console.log(`📊 Filas encontradas: ${filas.length}`);
        for (let i = 0; i < filas.length; i++) {
            try {
                contador++;
                const fila = filas[i];
                // 1️ Obtener tracking
                const tracking = await fila.$eval('td:nth-child(2)', el => el.innerText.trim());

                console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
                console.log(`:::::::::::::::::: Registros ${contador} de ${totalElementos} ::::::::::::::::::`);
                console.log(`:::::::::::::::::: Procesando Guía: ${tracking} ::::::::::::::::::`);
                console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);

                const resultado = {
                    option: "store",
                    id_location: id_location,
                    phone: "",
                    receiver: "",
                    address: "",
                    id_user: id_user,
                    tracking: tracking,
                    id_cat_parcel: 3,
                    marker: colorFinal,
                    estado: ""
                };

                // 2️ Click en ojito
                const ojo = await fila.$('td:nth-child(13) svg use');
                if (ojo) {
                    await ojo.evaluate(el => {
                        el.dispatchEvent(new MouseEvent('click', { bubbles: true }));
                    });
                    await page.waitForTimeout(800);
                }

                // 3️ Extraer datos
                const data = await fila.evaluate(tr => {
                    const tds = tr.querySelectorAll('td');

                    return {
                        tracking: tds[1]?.innerText.trim(),
                        direccion: tds[11]?.innerText.trim(),
                        nombre: tds[12]?.innerText.trim(),
                        telefono: tds[13]?.innerText.trim()
                    };
                });

                const nameR = data.nombre;
                const telR = data.telefono;
                const addrR = data.direccion;

                // 🔍 VALIDACIÓN
                let datosValidos = true;

                if (!nameR || nameR.trim() === "") {
                    console.log("❌ Nombre del receptor vacío");
                    datosValidos = false;
                    resultado.estado = "Falló: Nombre receptor vacío";
                }

                if (telR.includes("*")) {
                    console.log("❌ Teléfono con asteriscos");
                    datosValidos = false;
                    resultado.estado = "Falló: Teléfono con asteriscos";
                }

                // Limpiar teléfono: dejar solo números
                const telefonoLimpio = telR.replace(/\D/g, '');

                // Tomar últimos 10 dígitos
                const telefonoFinal = telefonoLimpio.slice(-10);

                // Validar
                if (!/^\d{10}$/.test(telefonoFinal)) {
                    console.log("❌ Teléfono inválido");
                    datosValidos = false;
                    resultado.estado = "Falló: Teléfono inválido";
                } else {
                    resultado.phone = telefonoFinal;
                }

                // Asignar datos
                resultado.receiver = nameR;
                //resultado.phone = telR;
                resultado.address = addrR;

                // 🚀 ENVÍO
                if (datosValidos) {
                    console.log(`✅ Datos válidos: ${nameR} | ${telR}`);

                    try {
                        const respuestaServidor = await enviarDatos(resultado);

                        if (respuestaServidor.success === "true") {
                            resultado.estado = "Registrado";
                        } else {
                            const msg = respuestaServidor.message || "Sin mensaje";
                            resultado.estado = "Falló: " + msg.replace(/["']/g, "");
                        }

                    } catch (error) {
                        resultado.estado = "Falló: Error de conexión";
                        console.error("❌ Error al enviar:", error);
                    }

                } else {
                    console.log(`⏸️ No enviado: ${nameR} | ${telR}`);
                }

                resultados.push(resultado);

            } catch (error) {
                console.error(`❌ Error en fila ${i + 1}:`, error.message);
            }
        }
        await page.waitForTimeout(1000);

        console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
        console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
        console.log("📊 FIN DEL PROCESO:");

        const guiasRegistradas = resultados.filter(r => r.estado === "Registrado");
        const guiasConError = resultados.filter(r => r.estado !== "Registrado" && r.estado.includes("Falló"));

        console.log(`📦 Total procesado: ${resultados.length}`);
        console.log(`✅ Guías registradas correctamente: ${guiasRegistradas.length}`);

        if (guiasConError.length > 0) {
            console.log(`❌ Guías con errores: ${guiasConError.length}`);
            console.log("\n🔍 Detalle de errores:");

            guiasConError.forEach((resultado, index) => {
                console.log(`\n${index + 1}. Guía: ${resultado.tracking}`);
                console.log(`Estado: ${resultado.estado}`);
                console.log(`Receptor: ${resultado.receiver || "No disponible"}`);
                console.log(`Teléfono: ${resultado.phone || "No disponible"}`);
            });
        }

    } catch (error) {
        console.error("❌ Error:", error.message);
    }
} else {
    console.log("❌ Proceso cancelado por el usuario");
}