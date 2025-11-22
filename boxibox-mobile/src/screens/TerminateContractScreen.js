import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import contractService from '../services/contractService';
import Button from '../components/Button';
import Input from '../components/Input';
import Card from '../components/Card';
import { COLORS } from '../constants/colors';

const TerminateContractScreen = ({ route, navigation }) => {
  const { contract } = route.params;
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState({
    requested_termination_date: '',
    reason: '',
  });
  const [errors, setErrors] = useState({});

  const handleChange = (field, value) => {
    setFormData({ ...formData, [field]: value });
    if (errors[field]) {
      setErrors({ ...errors, [field]: undefined });
    }
  };

  const handleSubmit = async () => {
    setErrors({});

    if (!formData.requested_termination_date || !formData.reason) {
      Alert.alert('Erreur', 'Veuillez remplir tous les champs requis');
      return;
    }

    setLoading(true);

    try {
      await contractService.requestTermination(contract.id, formData);

      Alert.alert(
        'Demande envoyée',
        'Votre demande de résiliation a été envoyée avec succès. Vous serez contacté prochainement.',
        [
          {
            text: 'OK',
            onPress: () => navigation.goBack(),
          },
        ]
      );
    } catch (error) {
      console.error('Error requesting termination:', error);
      setErrors(error.errors || {});
      Alert.alert('Erreur', error.message || 'Impossible d\'envoyer la demande');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.container}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        style={styles.keyboardView}
      >
        <ScrollView
          style={styles.scrollView}
          keyboardShouldPersistTaps="handled"
        >
          <Card style={styles.warningCard}>
            <Text style={styles.warningTitle}>⚠️ Attention</Text>
            <Text style={styles.warningText}>
              La résiliation d'un contrat est irréversible. Assurez-vous de vider complètement
              votre box avant la date de résiliation demandée.
            </Text>
          </Card>

          <Card>
            <Text style={styles.sectionTitle}>Informations du contrat</Text>
            <View style={styles.infoRow}>
              <Text style={styles.label}>Numéro de contrat</Text>
              <Text style={styles.value}>{contract.contract_number}</Text>
            </View>
            <View style={styles.infoRow}>
              <Text style={styles.label}>Box</Text>
              <Text style={styles.value}>{contract.box?.number}</Text>
            </View>
            <View style={styles.infoRow}>
              <Text style={styles.label}>Montant mensuel</Text>
              <Text style={styles.value}>{contract.total_monthly_amount?.toFixed(2)} €</Text>
            </View>
          </Card>

          <Card>
            <Text style={styles.sectionTitle}>Demande de résiliation</Text>

            <Text style={styles.hint}>
              La date de résiliation doit respecter le préavis prévu dans votre contrat
              (généralement 1 mois).
            </Text>

            <Input
              label="Date de résiliation souhaitée *"
              value={formData.requested_termination_date}
              onChangeText={(value) => handleChange('requested_termination_date', value)}
              placeholder="AAAA-MM-JJ (ex: 2025-12-31)"
              error={errors.requested_termination_date?.[0]}
            />

            <Input
              label="Motif de la résiliation *"
              value={formData.reason}
              onChangeText={(value) => handleChange('reason', value)}
              placeholder="Expliquez brièvement la raison de votre résiliation..."
              multiline
              numberOfLines={6}
              style={styles.textArea}
              error={errors.reason?.[0]}
            />
          </Card>

          <Card style={styles.infoCard}>
            <Text style={styles.infoTitle}>📋 Procédure</Text>
            <Text style={styles.infoText}>
              1. Votre demande sera examinée par notre équipe{'\n'}
              2. Vous recevrez une confirmation par email{'\n'}
              3. Videz votre box avant la date de résiliation{'\n'}
              4. L'état des lieux sera effectué{'\n'}
              5. Votre caution vous sera restituée
            </Text>
          </Card>

          <View style={styles.actions}>
            <Button
              title="Envoyer la demande"
              onPress={handleSubmit}
              loading={loading}
            />
            <Button
              title="Annuler"
              onPress={() => navigation.goBack()}
              variant="outline"
              style={styles.cancelButton}
            />
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  keyboardView: {
    flex: 1,
  },
  scrollView: {
    flex: 1,
    padding: 16,
  },
  warningCard: {
    backgroundColor: COLORS.warning + '10',
    borderLeftWidth: 4,
    borderLeftColor: COLORS.warning,
  },
  warningTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.warning,
    marginBottom: 8,
  },
  warningText: {
    fontSize: 14,
    color: COLORS.text.primary,
    lineHeight: 20,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 12,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 6,
  },
  label: {
    fontSize: 14,
    color: COLORS.text.secondary,
  },
  value: {
    fontSize: 14,
    color: COLORS.text.primary,
    fontWeight: '600',
  },
  hint: {
    fontSize: 12,
    color: COLORS.text.secondary,
    marginBottom: 16,
    lineHeight: 18,
  },
  textArea: {
    minHeight: 120,
    textAlignVertical: 'top',
  },
  infoCard: {
    backgroundColor: COLORS.info + '10',
    borderLeftWidth: 4,
    borderLeftColor: COLORS.info,
  },
  infoTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: COLORS.info,
    marginBottom: 8,
  },
  infoText: {
    fontSize: 14,
    color: COLORS.text.primary,
    lineHeight: 22,
  },
  actions: {
    marginBottom: 24,
  },
  cancelButton: {
    marginTop: 12,
  },
});

export default TerminateContractScreen;
