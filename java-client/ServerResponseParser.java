package com.modintro.restfullclient;

/**
 * NOT A GENERAL XML PARSER 
 * Designed for web server responses no
 * more than 2 level deep.
 *
 * @author Craig Spencer
 */

import java.io.StringReader;
import java.util.ArrayList;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;

import org.w3c.dom.Document;
import org.w3c.dom.Element;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;
import org.xml.sax.InputSource;

public class ServerResponseParser {
	
	public ServerResponseParser() {
		try {
			Document doc = loadXMLFromString(xml);
			
			// Get column names
			Node node = doc.getFirstChild();
			Node nextNode = node.getFirstChild().getFirstChild();
			ArrayList<String> list = new ArrayList<String>();
			while(nextNode != null) {
				StringBuffer nodeName = new StringBuffer(nextNode.getNodeName());
				nodeName.setCharAt(0, Character.toUpperCase(nodeName.charAt(0)));
				list.add(nodeName.toString());
				nextNode = nextNode.getNextSibling();
			}
			
			// Move column names from list to array
			int listSize = list.size();
			columnNames = new String[listSize];
			for(int i = 0; i < listSize; i++) {
				columnNames[i] = list.get(i);
			}
			
			// Get data
			list = new ArrayList<String>();
			NodeList nodelist = doc.getElementsByTagName("*");
			for(int i = 0; i < nodelist.getLength(); i++) {
				Node currentNode = nodelist.item(i);
				if (currentNode.getNodeType() == Node.ELEMENT_NODE) {
					Node temp = currentNode.getFirstChild();
					if(temp.getNodeType() == Node.TEXT_NODE)
						list.add(temp.getNodeValue());
						// System.out.println(temp.getNodeType() + " " + temp.getNodeValue());
				}
			}
			
			// Move data to 2D array
			listSize = list.size()/columnNames.length;
			int index = 0;
			data = new Object[listSize][columnNames.length];
			for(int i = 0; i < listSize; i++) {
				for(int j = 0; j < columnNames.length; j++) {
					data[i][j] = list.get(index);
					index++;
				}
			}
			
			// Check
			/*
			for(int i = 0; i < columnNames.length; i++)
				System.out.print(columnNames[i] + " ");
			System.out.println("CHECK");
			for(int i = 0; i < data.length; i++) {
				for(int j = 0; j < data[0].length; j++) {
					System.out.print(data[i][j] + " ");
				}
				System.out.println();
			}
			*/
			
		} catch(Exception e) {
			System.out.println(e.getMessage() + " " + e.toString());
		}
	}	   
	
	public Document loadXMLFromString(String xml) throws Exception
	{
	    DocumentBuilderFactory factory = DocumentBuilderFactory.newInstance();
	    DocumentBuilder builder = factory.newDocumentBuilder();
	    InputSource is = new InputSource(new StringReader(xml));
	    return builder.parse(is);
	}
	
	public String[] getColumnNames() {
		return columnNames;
	}
	
	public Object[][] getData() {
		return data;
	}
	
	private static String[] columnNames;
	private static Object[][] data;
	
	 static String xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>" +
			"<notes>" +
			"<note>" + 
	   		"<to>Tove</to>" +
	   		"<from>Jani</from>" +
	   		"<heading>Reminder</heading>" +
	   		"<body>Don't forget me this weekend.</body>" +
	   		"</note>" +
	   		"<note>" + 
	   		"<to>Gia</to>" +
	   		"<from>Bob</from>" +
	   		"<heading>Knock it off!</heading>" +
	   		"<body>Please, stop leaving the sponge in the sink.</body>" +
	   		"</note>" +
	   		"<note>" + 
	   		"<to>Jim</to>" +
	   		"<from>Mary</from>" +
	   		"<heading>Hey!</heading>" +
	   		"<body>Your team lost! Suck it!</body>" +
	   		"</note>" +
	   		"</notes>";

}
