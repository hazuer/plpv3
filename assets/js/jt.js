//Powered By HaZuEr.Ing
//Version:08072025
// Solicitar los números de seguimiento mediante un prompt
const input = prompt("👾 Ingresa los números de guía J&T [📦]:");
// Procesar el input para crear el array
const trackingNumbers = input 
    ? input.split('\n')          // Dividir por saltos de línea
           .map(num => num.trim()) // Limpiar espacios
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
1 - TQL
2 - ZAC`) || 1;

const id_user = (id_location == 1) ? 2 : 4;  // Si es 1 (TQL), asigna usuario 2 (karen); si no, asigna 4 (josue)

// Generar mensaje de confirmación
const guiaInicial = trackingNumbers[0] || "N/A";
const guiaFinal = trackingNumbers[trackingNumbers.length - 1] || "N/A";
const totalGuias = trackingNumbers.length;

const mensajeConfirmacion = `
¿👾 Los datos son correctos? [⚙️]:
---------------------------------
🔢 Total de guías: ${totalGuias}
📦 Guía inicial: ${guiaInicial}
📦 Guía final: ${guiaFinal}
---------------------------------
🎨 Color: ${colorMap[color]}
📍 Ubicación: ${id_location == 1 ? "TQL" : "ZAC"}`;

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
    for (const trackingNumber of trackingNumbers) {
        contador++;
        const resultado = {
            option       : "store",
            id_location  : id_location,
            phone        : "",
            receiver     : "",
            address      : "",
            id_user      : id_user,
            tracking     : trackingNumber,
            id_cat_parcel: 1, //JMX
            marker       : colorFinal,
            estado       : ""
        };
        try {
            await page.goto("https://jmx.jtjms-mx.com/app/serviceQualityIndex/recordSheet?title=Orden%20de%20registro&moduleCode=");
            await page.waitForTimeout(2300);
            try {
                await page.waitForSelector(`input[placeholder="Por favor, ingrese"]`, { timeout: 2000 });
            } catch {
                console.log("No se encontró el input en español, recargando...");
                await page.reload();
                await page.waitForSelector(`input[placeholder="Por favor, ingrese"]`, { timeout: 3000 });
            }
            const input = await page.$(`input[placeholder="Por favor, ingrese"]`);
            await input.click();
            await page.evaluate((inputElement, text) => {
                inputElement.value = text;
                const event = new Event("input", { bubbles: true });
                inputElement.dispatchEvent(event);
            }, input, trackingNumber);
            console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
            console.log(`:::::::::::::::::: Procesando ${contador} de ${totalElementos} ::::::::::::::::::`);
            console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
            const currentValue = await page.evaluate(el => el.value, input);
            if (currentValue !== trackingNumber) {
                throw new Error("Error al pegar el texto");
            }
            console.log("✅ Texto pegado correctamente");
            // Wait and click "Información básica" tab
            await page.waitForTimeout(600);
            await page.waitForSelector("#tab-base.el-tabs__item", { timeout: 800 });
            await page.click("#tab-base.el-tabs__item");
            console.log(`✅ Pestaña "Información básica" clickeada`);
            await page.waitForTimeout(1000);
            // Click on the second info icons
            try {
                await page.waitForSelector(".iconfuwuzhiliang-mingwen", { timeout: 1000 });
                const icons = await page.$$(".iconfuwuzhiliang-mingwen");
                console.log(`🔍 Íconos encontrados: ${icons.length}`);

                if (icons.length >= 2) {
                    try {
                        await icons[1].hover();
                        await icons[1].click();
                        await page.waitForTimeout(200);
                        console.log(`✅ Segundo ícono clickeado`);
                    } catch (error) {
                        console.warn(`⚠️ Error al hacer clic en el segundo ícono:`, error.message);
                    }
                } else {
                    console.warn("⚠️ No hay al menos dos íconos disponibles para hacer clic.");
                }
            } catch (error) {
                console.error("❌ Error al buscar los íconos:", error.message);
            }
            await page.waitForTimeout(100);
            await page.waitForSelector(".item .row", { timeout: 2800 });
            const [nameR, telR, addrR] = await page.evaluate(() => {
                const rows    = Array.from(document.querySelectorAll(".item .row"));
                const nameRow = rows.find(row => row.textContent.includes("Nombre del receptor:"));
                const telRow  = rows.find(row => row.textContent.includes("Teléfono del destinatario:"));
                const addRow  = rows.find(row => row.textContent.includes("Dirección de destinatario:"));
                const nameR   = nameRow ? nameRow.querySelector("span").textContent.trim() : "";
                let telR      = telRow ? telRow.querySelector("span").textContent.trim() : "";
                const addrR   = addRow ? addRow.querySelector("span").textContent.trim().replace(/\s+/g, ' ').trim() : "";
                telR          = telR.slice(-10);
                return [nameR, telR, addrR];
            });
            // Validación de datos antes del envío
            let datosValidos = true;
            if (!nameR || nameR.trim() === "") {
                console.log("❌ Nombre del receptor está vacío - No se enviará al endpoint");
                datosValidos = false;
                resultado.estado = "Falló: Nombre receptor vacío";
            }
            if (telR.includes("*")) {
                console.log("❌ Teléfono contiene asteriscos - No se enviará al endpoint");
                datosValidos = false;
                resultado.estado = "Falló: Teléfono con asteriscos";
            }
            if (!/^\d{10}$/.test(telR)) {
                console.log("❌ Teléfono no tiene 10 dígitos - No se enviará al endpoint");
                datosValidos = false;
                resultado.estado = "Falló: Teléfono inválido";
            }
            resultado.receiver = nameR;
            resultado.phone    = telR;
            resultado.address  = addrR;
            if (datosValidos) {
                console.log(`✅ Datos válidos: ${nameR} | ${telR} | ${addrR}`);
                try {
                    const respuestaServidor = await enviarDatos(resultado);
                    if (respuestaServidor.success === "true") {
                        resultado.estado = "Registrado";
                    } else {
                        const msg = respuestaServidor.message || "Sin mensaje del servidor";
                        resultado.estado = "Falló: " + msg.replace(/["']/g, "");
                    }
                } catch (error) {
                    resultado.estado = "Falló: Error de conexión";
                    console.error("Error al enviar datos:", error);
                }
            } else {
                console.log(`⏸️ Datos no enviados: ${nameR} | ${telR} - Motivo: ${resultado.estado}`);
            }
        } catch (error) {
            console.error(`❌ Error al procesar ${trackingNumber}:`, error.message);
            resultado.estado = `Falló: ${error.message}`;
        } finally {
            resultados.push(resultado);
        }
    } // end for
    await page.waitForTimeout(1000);
    console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
    console.log(`:::::::::::::::::::::::::::::::::::::::::::::::::::::::::`);
    console.log("📊 FIN DEL PROCESO:");
    // Filtrar y contar resultados
    const guiasRegistradas = resultados.filter(r => r.estado === "Registrado");
    const guiasConError    = resultados.filter(r => r.estado !== "Registrado" && r.estado.includes("Falló")); // Asegura que solo cuente los fallos reales
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
} else {
    console.log("❌ Proceso cancelado por el usuario");
}