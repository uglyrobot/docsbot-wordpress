import fs from "node:fs";
import path from "node:path";

const translations = {
	"de_DE": {
		"Add MCP server": "MCP-Server hinzufügen",
		"Add MCP server in DocsBot": "MCP-Server in DocsBot hinzufügen",
		"Add skill": "Skill hinzufügen",
		"Add skill in DocsBot": "Skill in DocsBot hinzufügen",
		"Allow the bot to detect when a user needs to speak to a human.": "Dem Bot erlauben zu erkennen, wann ein Benutzer mit einem Menschen sprechen möchte.",
		"Button Label": "Schaltflächenbeschriftung",
		"Button Link": "Schaltflächenlink",
		"Collect Feedback": "Feedback erfassen",
		"Collect ratings (CSAT) from users after they interact with the bot.": "Bewertungen (CSAT) von Benutzern nach der Interaktion mit dem Bot erfassen.",
		"Connect your bot to external tools and data from your services.": "Verbinden Sie Ihren Bot mit externen Tools und Daten aus Ihren Diensten.",
		"DocsBot authentication failed. Replace the API key and reconnect.": "Die DocsBot-Authentifizierung ist fehlgeschlagen. Ersetzen Sie den API-Schlüssel und stellen Sie die Verbindung erneut her.",
		"Custom Buttons": "Benutzerdefinierte Schaltflächen",
		"Enable bot skills to give your bot special abilities.": "Aktivieren Sie Bot-Skills, um Ihrem Bot besondere Fähigkeiten zu verleihen.",
		"Human Support Escalation": "Weiterleitung an menschlichen Support",
		"Let your bot show a configured button when its instructions match.": "Lassen Sie Ihren Bot eine konfigurierte Schaltfläche anzeigen, wenn die Anweisungen zutreffen.",
		"MCP Servers": "MCP-Server",
		"New!": "Neu!",
		"Scheduling Tools": "Planungstools",
		"Skills": "Skills",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "Öffnen Sie ein eingebettetes Buchungs-Widget für Calendly, Cal.com oder TidyCal.",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "Dieser API-Schlüssel hat keine Berechtigung für diesen DocsBot-Vorgang. Bitten Sie einen Team-Inhaber oder Administrator um den erforderlichen Bot-Zugriff.",
		"Web Search": "Websuche",
	},
	"es_ES": {
		"Add MCP server": "Añadir servidor MCP",
		"Add MCP server in DocsBot": "Añadir servidor MCP en DocsBot",
		"Add skill": "Añadir habilidad",
		"Add skill in DocsBot": "Añadir habilidad en DocsBot",
		"Allow the bot to detect when a user needs to speak to a human.": "Permite que el bot detecte cuándo un usuario necesita hablar con una persona.",
		"Button Label": "Etiqueta del botón",
		"Button Link": "Enlace del botón",
		"Collect Feedback": "Recopilar comentarios",
		"Collect ratings (CSAT) from users after they interact with the bot.": "Recopila valoraciones (CSAT) de los usuarios después de interactuar con el bot.",
		"Connect your bot to external tools and data from your services.": "Conecta tu bot a herramientas externas y datos de tus servicios.",
		"DocsBot authentication failed. Replace the API key and reconnect.": "La autenticación de DocsBot ha fallado. Sustituye la clave de API y vuelve a conectar.",
		"Custom Buttons": "Botones personalizados",
		"Enable bot skills to give your bot special abilities.": "Activa habilidades para dar a tu bot capacidades especiales.",
		"Human Support Escalation": "Escalado a soporte humano",
		"Let your bot show a configured button when its instructions match.": "Permite que tu bot muestre un botón configurado cuando se cumplan sus instrucciones.",
		"MCP Servers": "Servidores MCP",
		"New!": "¡Nuevo!",
		"Scheduling Tools": "Herramientas de programación",
		"Skills": "Habilidades",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "Abre un widget de reservas integrado para Calendly, Cal.com o TidyCal.",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "Esta clave de API no tiene permiso para esa operación de DocsBot. Solicita al propietario o administrador del equipo el acceso necesario al bot.",
		"Web Search": "Búsqueda web",
	},
	"fr_FR": {
		"Add MCP server": "Ajouter un serveur MCP",
		"Add MCP server in DocsBot": "Ajouter un serveur MCP dans DocsBot",
		"Add skill": "Ajouter une compétence",
		"Add skill in DocsBot": "Ajouter une compétence dans DocsBot",
		"Allow the bot to detect when a user needs to speak to a human.": "Permet au bot de détecter lorsqu’un utilisateur souhaite parler à une personne.",
		"Button Label": "Libellé du bouton",
		"Button Link": "Lien du bouton",
		"Collect Feedback": "Recueillir des avis",
		"Collect ratings (CSAT) from users after they interact with the bot.": "Recueille les évaluations (CSAT) des utilisateurs après leur interaction avec le bot.",
		"Connect your bot to external tools and data from your services.": "Connectez votre bot aux outils externes et aux données de vos services.",
		"DocsBot authentication failed. Replace the API key and reconnect.": "L’authentification DocsBot a échoué. Remplacez la clé API et reconnectez-vous.",
		"Custom Buttons": "Boutons personnalisés",
		"Enable bot skills to give your bot special abilities.": "Activez les compétences du bot pour lui donner des capacités particulières.",
		"Human Support Escalation": "Transfert vers l’assistance humaine",
		"Let your bot show a configured button when its instructions match.": "Permet à votre bot d’afficher un bouton configuré lorsque ses instructions correspondent.",
		"MCP Servers": "Serveurs MCP",
		"New!": "Nouveau !",
		"Scheduling Tools": "Outils de planification",
		"Skills": "Compétences",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "Ouvre un widget de réservation intégré pour Calendly, Cal.com ou TidyCal.",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "Cette clé API n’est pas autorisée à effectuer cette opération DocsBot. Demandez au propriétaire ou à l’administrateur de l’équipe l’accès requis au bot.",
		"Web Search": "Recherche Web",
	},
	"ja": {
		"Add MCP server": "MCPサーバーを追加",
		"Add MCP server in DocsBot": "DocsBotでMCPサーバーを追加",
		"Add skill": "スキルを追加",
		"Add skill in DocsBot": "DocsBotでスキルを追加",
		"Allow the bot to detect when a user needs to speak to a human.": "ユーザーが担当者との会話を必要としていることをボットが検出できるようにします。",
		"Button Label": "ボタンのラベル",
		"Button Link": "ボタンのリンク",
		"Collect Feedback": "フィードバックを収集",
		"Collect ratings (CSAT) from users after they interact with the bot.": "ボットとの対話後にユーザーから評価（CSAT）を収集します。",
		"Connect your bot to external tools and data from your services.": "ボットを外部ツールやサービスのデータに接続します。",
		"DocsBot authentication failed. Replace the API key and reconnect.": "DocsBotの認証に失敗しました。APIキーを交換して再接続してください。",
		"Custom Buttons": "カスタムボタン",
		"Enable bot skills to give your bot special abilities.": "ボットスキルを有効にして、特別な機能を追加します。",
		"Human Support Escalation": "有人サポートへのエスカレーション",
		"Let your bot show a configured button when its instructions match.": "指示に一致したときに、設定済みのボタンをボットに表示させます。",
		"MCP Servers": "MCPサーバー",
		"New!": "新機能",
		"Scheduling Tools": "予約ツール",
		"Skills": "スキル",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "Calendly、Cal.com、TidyCalの埋め込み予約ウィジェットを開きます。",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "このAPIキーには、そのDocsBot操作を行う権限がありません。必要なボットアクセス権をチームの所有者または管理者に依頼してください。",
		"Web Search": "ウェブ検索",
	},
	"pt_BR": {
		"Add MCP server": "Adicionar servidor MCP",
		"Add MCP server in DocsBot": "Adicionar servidor MCP no DocsBot",
		"Add skill": "Adicionar habilidade",
		"Add skill in DocsBot": "Adicionar habilidade no DocsBot",
		"Allow the bot to detect when a user needs to speak to a human.": "Permite que o bot detecte quando um usuário precisa falar com uma pessoa.",
		"Button Label": "Rótulo do botão",
		"Button Link": "Link do botão",
		"Collect Feedback": "Coletar feedback",
		"Collect ratings (CSAT) from users after they interact with the bot.": "Coleta avaliações (CSAT) dos usuários após a interação com o bot.",
		"Connect your bot to external tools and data from your services.": "Conecte seu bot a ferramentas externas e dados dos seus serviços.",
		"DocsBot authentication failed. Replace the API key and reconnect.": "A autenticação do DocsBot falhou. Substitua a chave de API e reconecte.",
		"Custom Buttons": "Botões personalizados",
		"Enable bot skills to give your bot special abilities.": "Ative habilidades para dar recursos especiais ao seu bot.",
		"Human Support Escalation": "Escalonamento para suporte humano",
		"Let your bot show a configured button when its instructions match.": "Permite que seu bot mostre um botão configurado quando as instruções correspondentes forem atendidas.",
		"MCP Servers": "Servidores MCP",
		"New!": "Novo!",
		"Scheduling Tools": "Ferramentas de agendamento",
		"Skills": "Habilidades",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "Abre um widget de agendamento incorporado para Calendly, Cal.com ou TidyCal.",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "Esta chave de API não tem permissão para essa operação do DocsBot. Solicite ao proprietário ou administrador da equipe o acesso necessário ao bot.",
		"Web Search": "Pesquisa na web",
	},
	"zh_CN": {
		"Add MCP server": "添加 MCP 服务器",
		"Add MCP server in DocsBot": "在 DocsBot 中添加 MCP 服务器",
		"Add skill": "添加技能",
		"Add skill in DocsBot": "在 DocsBot 中添加技能",
		"Allow the bot to detect when a user needs to speak to a human.": "允许机器人检测用户何时需要与人工客服交谈。",
		"Button Label": "按钮标签",
		"Button Link": "按钮链接",
		"Collect Feedback": "收集反馈",
		"Collect ratings (CSAT) from users after they interact with the bot.": "在用户与机器人互动后收集评分（CSAT）。",
		"Connect your bot to external tools and data from your services.": "将机器人连接到外部工具和服务数据。",
		"DocsBot authentication failed. Replace the API key and reconnect.": "DocsBot 身份验证失败。请更换 API 密钥并重新连接。",
		"Custom Buttons": "自定义按钮",
		"Enable bot skills to give your bot special abilities.": "启用机器人技能，为机器人提供特殊能力。",
		"Human Support Escalation": "转接人工支持",
		"Let your bot show a configured button when its instructions match.": "当符合指令时，让机器人显示已配置的按钮。",
		"MCP Servers": "MCP 服务器",
		"New!": "新功能！",
		"Scheduling Tools": "预约工具",
		"Skills": "技能",
		"Trigger an embedded booking widget for Calendly, Cal.com, or TidyCal.": "打开 Calendly、Cal.com 或 TidyCal 的嵌入式预约组件。",
		"This API key does not have permission for that DocsBot operation. Ask a team owner or admin for the required bot access.": "此 API 密钥无权执行该 DocsBot 操作。请向团队所有者或管理员申请所需的机器人访问权限。",
		"Web Search": "网页搜索",
	},
};

const editorTranslations = {
	"de_DE": {
		"Add custom button": "Benutzerdefinierte Schaltfläche hinzufügen",
		"Booking action": "Buchungsaktion",
		"Button text": "Schaltflächentext",
		"Calendar": "Kalender",
		"Cart": "Warenkorb",
		"Chat": "Chat",
		"Custom button": "Benutzerdefinierte Schaltfläche",
		"Email": "E-Mail",
		"Every custom button needs a unique key.": "Jede benutzerdefinierte Schaltfläche benötigt einen eindeutigen Schlüssel.",
		"External link": "Externer Link",
		"Hide event details": "Veranstaltungsdetails ausblenden",
		"Hide the cookie banner": "Cookie-Banner ausblenden",
		"Hide the profile avatar": "Profilbild ausblenden",
		"Icon": "Symbol",
		"Key": "Schlüssel",
		"MCP server removed from the widget.": "MCP-Server aus dem Widget entfernt.",
		"Name": "Name",
		"New custom button": "Neue benutzerdefinierte Schaltfläche",
		"Phone": "Telefon",
		"Remove": "Entfernen",
		"Remove button": "Schaltfläche entfernen",
		"Skill removed from the widget.": "Skill aus dem Widget entfernt.",
		"That MCP server is not available for this bot.": "Dieser MCP-Server ist für diesen Bot nicht verfügbar.",
		"That skill is not available for this bot.": "Dieser Skill ist für diesen Bot nicht verfügbar.",
		"The selected skill ID is invalid.": "Die ausgewählte Skill-ID ist ungültig.",
		"Ticket": "Ticket",
		"URL": "URL",
		"When to trigger": "Wann auslösen",
		"When to use": "Wann verwenden",
	},
	"es_ES": {
		"Add custom button": "Añadir botón personalizado", "Booking action": "Acción de reserva", "Button text": "Texto del botón", "Calendar": "Calendario", "Cart": "Carrito", "Chat": "Chat", "Custom button": "Botón personalizado", "Email": "Correo electrónico", "Every custom button needs a unique key.": "Cada botón personalizado necesita una clave única.", "External link": "Enlace externo", "Hide event details": "Ocultar detalles del evento", "Hide the cookie banner": "Ocultar el aviso de cookies", "Hide the profile avatar": "Ocultar el avatar del perfil", "Icon": "Icono", "Key": "Clave", "MCP server removed from the widget.": "Servidor MCP eliminado del widget.", "Name": "Nombre", "New custom button": "Nuevo botón personalizado", "Phone": "Teléfono", "Remove": "Eliminar", "Remove button": "Eliminar botón", "Skill removed from the widget.": "Habilidad eliminada del widget.", "That MCP server is not available for this bot.": "Ese servidor MCP no está disponible para este bot.", "That skill is not available for this bot.": "Esa habilidad no está disponible para este bot.", "The selected skill ID is invalid.": "El ID de habilidad seleccionado no es válido.", "Ticket": "Ticket", "URL": "URL", "When to trigger": "Cuándo activar", "When to use": "Cuándo usar",
	},
	"fr_FR": {
		"Add custom button": "Ajouter un bouton personnalisé", "Booking action": "Action de réservation", "Button text": "Texte du bouton", "Calendar": "Calendrier", "Cart": "Panier", "Chat": "Discussion", "Custom button": "Bouton personnalisé", "Email": "E-mail", "Every custom button needs a unique key.": "Chaque bouton personnalisé doit avoir une clé unique.", "External link": "Lien externe", "Hide event details": "Masquer les détails de l’événement", "Hide the cookie banner": "Masquer la bannière de cookies", "Hide the profile avatar": "Masquer l’avatar du profil", "Icon": "Icône", "Key": "Clé", "MCP server removed from the widget.": "Serveur MCP retiré du widget.", "Name": "Nom", "New custom button": "Nouveau bouton personnalisé", "Phone": "Téléphone", "Remove": "Retirer", "Remove button": "Retirer le bouton", "Skill removed from the widget.": "Compétence retirée du widget.", "That MCP server is not available for this bot.": "Ce serveur MCP n’est pas disponible pour ce bot.", "That skill is not available for this bot.": "Cette compétence n’est pas disponible pour ce bot.", "The selected skill ID is invalid.": "L’identifiant de compétence sélectionné n’est pas valide.", "Ticket": "Ticket", "URL": "URL", "When to trigger": "Quand déclencher", "When to use": "Quand utiliser",
	},
	"ja": {
		"Add custom button": "カスタムボタンを追加", "Booking action": "予約アクション", "Button text": "ボタンのテキスト", "Calendar": "カレンダー", "Cart": "カート", "Chat": "チャット", "Custom button": "カスタムボタン", "Email": "メール", "Every custom button needs a unique key.": "各カスタムボタンには一意のキーが必要です。", "External link": "外部リンク", "Hide event details": "イベントの詳細を非表示", "Hide the cookie banner": "Cookieバナーを非表示", "Hide the profile avatar": "プロフィール画像を非表示", "Icon": "アイコン", "Key": "キー", "MCP server removed from the widget.": "MCPサーバーをウィジェットから削除しました。", "Name": "名前", "New custom button": "新しいカスタムボタン", "Phone": "電話", "Remove": "削除", "Remove button": "ボタンを削除", "Skill removed from the widget.": "スキルをウィジェットから削除しました。", "That MCP server is not available for this bot.": "そのMCPサーバーはこのボットでは利用できません。", "That skill is not available for this bot.": "そのスキルはこのボットでは利用できません。", "The selected skill ID is invalid.": "選択したスキルIDは無効です。", "Ticket": "チケット", "URL": "URL", "When to trigger": "実行するタイミング", "When to use": "使用するタイミング",
	},
	"pt_BR": {
		"Add custom button": "Adicionar botão personalizado", "Booking action": "Ação de agendamento", "Button text": "Texto do botão", "Calendar": "Calendário", "Cart": "Carrinho", "Chat": "Chat", "Custom button": "Botão personalizado", "Email": "E-mail", "Every custom button needs a unique key.": "Cada botão personalizado precisa de uma chave exclusiva.", "External link": "Link externo", "Hide event details": "Ocultar detalhes do evento", "Hide the cookie banner": "Ocultar o banner de cookies", "Hide the profile avatar": "Ocultar o avatar do perfil", "Icon": "Ícone", "Key": "Chave", "MCP server removed from the widget.": "Servidor MCP removido do widget.", "Name": "Nome", "New custom button": "Novo botão personalizado", "Phone": "Telefone", "Remove": "Remover", "Remove button": "Remover botão", "Skill removed from the widget.": "Habilidade removida do widget.", "That MCP server is not available for this bot.": "Esse servidor MCP não está disponível para este bot.", "That skill is not available for this bot.": "Essa habilidade não está disponível para este bot.", "The selected skill ID is invalid.": "O ID da habilidade selecionada é inválido.", "Ticket": "Ticket", "URL": "URL", "When to trigger": "Quando acionar", "When to use": "Quando usar",
	},
	"zh_CN": {
		"Add custom button": "添加自定义按钮", "Booking action": "预约操作", "Button text": "按钮文本", "Calendar": "日历", "Cart": "购物车", "Chat": "聊天", "Custom button": "自定义按钮", "Email": "电子邮件", "Every custom button needs a unique key.": "每个自定义按钮都需要唯一的键。", "External link": "外部链接", "Hide event details": "隐藏活动详情", "Hide the cookie banner": "隐藏 Cookie 横幅", "Hide the profile avatar": "隐藏个人资料头像", "Icon": "图标", "Key": "键", "MCP server removed from the widget.": "已从小工具中移除 MCP 服务器。", "Name": "名称", "New custom button": "新建自定义按钮", "Phone": "电话", "Remove": "移除", "Remove button": "移除按钮", "Skill removed from the widget.": "已从小工具中移除技能。", "That MCP server is not available for this bot.": "该 MCP 服务器不适用于此机器人。", "That skill is not available for this bot.": "该技能不适用于此机器人。", "The selected skill ID is invalid.": "所选技能 ID 无效。", "Ticket": "工单", "URL": "URL", "When to trigger": "触发时机", "When to use": "使用时机",
	},
};

for (const [locale, messages] of Object.entries(editorTranslations)) {
	Object.assign(translations[locale], messages);
}

function quotePo(value) {
	return `"${value.replaceAll("\\", "\\\\").replaceAll('"', '\\"').replaceAll("\n", "\\n")}"`;
}

const source = "docsbot/includes/class-docsbot-admin.php";
const potFile = path.join("docsbot", "languages", "docsbot.pot");
let pot = fs.readFileSync(potFile, "utf8").trimEnd();
const actionMessages = Object.keys(translations.de_DE);

for (const msgid of actionMessages) {
	const marker = `msgid ${quotePo(msgid)}\n`;
	if (!pot.includes(marker)) {
		pot += `\n\n#: ${source}\nmsgid ${quotePo(msgid)}\nmsgstr ""`;
	}
}

fs.writeFileSync(potFile, `${pot}\n`);

for (const [locale, messages] of Object.entries(translations)) {
	const file = path.join("docsbot", "languages", `docsbot-${locale}.po`);
	let catalog = fs.readFileSync(file, "utf8").trimEnd();

	for (const [msgid, msgstr] of Object.entries(messages)) {
		const escaped = msgid.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
		const pattern = new RegExp(`(^|\\n)((?:#.*\\n)*msgid "${escaped.replaceAll('"', '\\"')}"\\n)msgstr "(?:[^"\\\\]|\\\\.)*"`, "m");
		if (!pattern.test(catalog)) {
			catalog += `\n\n#: ${source}\nmsgid ${quotePo(msgid)}\nmsgstr ${quotePo(msgstr)}`;
			continue;
		}
		catalog = catalog.replace(pattern, (_match, prefix, entry) => {
			const cleaned = entry
				.replace(/^#, ([^\n]*, )?fuzzy(, [^\n]*)?\n/m, (_line, before = "", after = "") => {
					const flags = `${before}${after}`.replace(/^, |, $/g, "");
					return flags ? `#, ${flags}\n` : "";
				});
			return `${prefix}${cleaned}msgstr ${quotePo(msgstr)}`;
		});
	}

	fs.writeFileSync(file, `${catalog}\n`);
}
